<?php

namespace App\Services;

use App\Exceptions\ZakatException;
use App\Models\Distribution;
use App\Models\DistributionItem;
use App\Models\DistributionRequest;
use App\Models\Fund;
use App\Models\FundReservation;
use App\Models\Mustahik;
use App\Models\Program;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DistributionService
{
    public function __construct(private readonly AuditService $audit, private readonly FundService $funds) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return Distribution::with(['mustahik:id,display_name,mustahik_number', 'fund:id,name', 'items'])->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->latest()->paginate($this->perPage($filters));
    }

    public function create(array $data): Distribution
    {
        $mustahik = Mustahik::find($data['mustahik_id']) ?? throw ZakatException::notFound('Mustahik tidak ditemukan.');
        $fund = Fund::find($data['fund_id']) ?? throw ZakatException::notFound('Fund tidak ditemukan.');
        if ($mustahik->status !== 'active') {
            throw ZakatException::conflict('Mustahik tidak aktif.');
        } if (! empty($data['program_id'])) {
            $program = Program::find($data['program_id']);
            if (! $program || $program->status !== 'active') {
                throw ZakatException::conflict('Program harus active.');
            }
        }
        $distribution = Distribution::create($data + ['distribution_number' => app(BusinessNumberService::class)->next('DST'), 'source_type' => $data['source_type'] ?? 'direct', 'currency' => $data['currency'] ?? $fund->currency, 'approved_amount' => 0, 'distributed_amount' => 0, 'status' => 'draft', 'created_by' => auth()->id()]);
        if (! empty($data['items'])) {
            foreach ($data['items'] as $item) {
                DistributionItem::create($item + ['distribution_id' => $distribution->id, 'total_value' => bcmul((string) ($item['quantity'] ?? 1), (string) ($item['unit_value'] ?? 0), 2)]);
            }
        } $this->audit->record('distribution_created', $distribution);

        return $this->find($distribution->id);
    }

    public function find(string $id): Distribution
    {
        return Distribution::with(['mustahik', 'fund', 'items'])->find($id) ?? throw ZakatException::notFound('Distribution tidak ditemukan.');
    }

    public function update(Distribution $distribution, array $data): Distribution
    {
        if ($distribution->status !== 'draft') {
            throw ZakatException::invalidTransition('Distribution sudah tidak dapat diubah.');
        } $distribution->fill($data)->save();
        $this->audit->record('distribution_updated', $distribution);

        return $this->find($distribution->id);
    }

    public function submit(Distribution $distribution): Distribution
    {
        $this->assertStatus($distribution, 'draft');
        $distribution->forceFill(['status' => 'pending_approval'])->saveQuietly();
        $this->audit->record('distribution_submitted', $distribution);

        return $distribution;
    }

    public function approve(Distribution $distribution): Distribution
    {
        $this->assertStatus($distribution, 'pending_approval');
        if ($distribution->created_by === auth()->id()) {
            throw ZakatException::forbidden('Maker tidak dapat menyetujui distribution sendiri.');
        } $distribution->forceFill(['status' => 'approved', 'approved_amount' => $distribution->requested_amount])->saveQuietly();
        $this->audit->record('distribution_approved', $distribution);

        return $distribution;
    }

    public function reserve(Distribution $distribution): Distribution
    {
        $this->assertStatus($distribution, 'approved');
        $this->funds->reservation($distribution->fund, ['amount' => $distribution->approved_amount, 'target_type' => 'distribution', 'target_id' => $distribution->id, 'reason' => 'Reservation '.$distribution->distribution_number]);
        $distribution->forceFill(['status' => 'reserved'])->saveQuietly();
        $this->audit->record('distribution_reserved', $distribution);

        return $distribution;
    }

    public function process(Distribution $distribution): Distribution
    {
        if (! in_array($distribution->status, ['reserved', 'scheduled'], true)) {
            throw ZakatException::invalidTransition('Distribution belum reserved atau scheduled.');
        } $distribution->forceFill(['status' => 'processing'])->saveQuietly();
        $this->audit->record('distribution_processing', $distribution);

        return $distribution;
    }

    public function schedule(Distribution $distribution): Distribution
    {
        $this->assertStatus($distribution, 'reserved');
        $distribution->forceFill(['status' => 'scheduled'])->saveQuietly();
        $this->audit->record('distribution_scheduled', $distribution);

        return $distribution;
    }

    public function complete(Distribution $distribution, array $data): Distribution
    {
        if ($distribution->status !== 'processing') {
            throw ZakatException::invalidTransition('Distribution belum processing.');
        } $amount = (string) ($data['amount'] ?? $distribution->approved_amount);
        $total = bcadd((string) $distribution->distributed_amount, $amount, 2);
        if (bccomp($total, (string) $distribution->approved_amount, 2) > 0) {
            throw ZakatException::conflict('Distributed amount melebihi approved amount.');
        }

        return DB::transaction(function () use ($distribution, $amount, $total) {
            $reservation = FundReservation::where('target_type', 'distribution')->where('target_id', $distribution->id)->where('status', 'active')->first();
            if ($reservation) {
                $this->funds->releaseReservation($reservation, 'Converted to actual distribution');
            } $this->funds->outflow($distribution->fund, ['amount' => $amount, 'movement_type' => 'distribution', 'source_type' => 'distribution', 'source_id' => $distribution->id, 'description' => $distribution->distribution_number]);
            $distribution->forceFill(['distributed_amount' => $total, 'distribution_date' => $data['distribution_date'] ?? today(), 'status' => bccomp($total, (string) $distribution->approved_amount, 2) === 0 ? 'completed' : 'partially_completed'])->saveQuietly();
            $this->audit->record($distribution->status === 'completed' ? 'distribution_completed' : 'distribution_partially_completed', $distribution);

            return $this->find($distribution->id);
        });
    }

    public function cancel(Distribution $distribution, string $reason): Distribution
    {
        if (in_array($distribution->status, ['completed', 'reversed', 'cancelled'], true)) {
            throw ZakatException::invalidTransition('Distribution tidak dapat dibatalkan.');
        } $reservation = FundReservation::where('target_type', 'distribution')->where('target_id', $distribution->id)->where('status', 'active')->first();
        if ($reservation) {
            $this->funds->releaseReservation($reservation, $reason);
        } $distribution->forceFill(['status' => 'cancelled', 'description' => $reason])->saveQuietly();
        $this->audit->record('distribution_cancelled', $distribution);

        return $distribution;
    }

    public function reverse(Distribution $distribution, string $reason): Distribution
    {
        if ($distribution->status !== 'completed') {
            throw ZakatException::invalidTransition('Hanya distribution completed yang dapat direverse.');
        } $this->funds->adjust(['fund_id' => $distribution->fund_id, 'adjustment_type' => 'increase', 'amount' => $distribution->distributed_amount, 'reason' => 'Reversal '.$distribution->distribution_number.': '.$reason]);
        $distribution->forceFill(['status' => 'reversed'])->saveQuietly();
        $this->audit->record('distribution_reversed', $distribution, context: ['reason' => $reason]);

        return $distribution;
    }

    public function createRequest(array $data): DistributionRequest
    {
        Mustahik::find($data['mustahik_id']) ?? throw ZakatException::notFound('Mustahik tidak ditemukan.');
        Fund::find($data['fund_id']) ?? throw ZakatException::notFound('Fund tidak ditemukan.');
        $request = DistributionRequest::create($data + ['request_number' => app(BusinessNumberService::class)->next('DSR'), 'requested_by' => auth()->id(), 'requested_at' => now(), 'status' => 'pending']);
        $this->audit->record('distribution_request_created', $request);

        return $request->load('mustahik', 'fund');
    }

    public function approveRequest(DistributionRequest $request): DistributionRequest
    {
        if ($request->status !== 'pending') {
            throw ZakatException::invalidTransition('Request tidak menunggu approval.');
        } $request->forceFill(['status' => 'approved'])->saveQuietly();
        $this->audit->record('distribution_request_approved', $request);

        return $request;
    }

    private function assertStatus(Distribution $distribution, string $status): void
    {
        if ($distribution->status !== $status) {
            throw ZakatException::invalidTransition("Distribution harus {$status}.");
        }
    }

    private function perPage(array $filters): int
    {
        return min((int) ($filters['per_page'] ?? 15), (int) config('zakat.pagination.max_per_page'));
    }
}
