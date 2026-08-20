<?php

namespace App\Services;

use App\Exceptions\ZakatException;
use App\Models\Collection;
use App\Models\Fund;
use App\Models\FundAllocation;
use App\Models\FundMovement;
use App\Models\FundReconciliation;
use App\Models\FundReservation;
use App\Models\FundTransfer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FundService
{
    public function __construct(private readonly AuditService $audit) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return Fund::query()->when($filters['fund_type'] ?? null, fn ($q, $v) => $q->where('fund_type', $v))->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->latest()->paginate(min((int) ($filters['per_page'] ?? 15), (int) config('zakat.pagination.max_per_page')));
    }

    public function create(array $data): Fund
    {
        return DB::transaction(function () use ($data) {
            $opening = (string) ($data['opening_balance'] ?? '0');
            $fund = Fund::create(['fund_code' => $data['fund_code'], 'name' => $data['name'], 'fund_type' => $data['fund_type'] ?? 'zakat', 'category' => $data['category'] ?? null, 'restriction_type' => $data['restriction_type'] ?? 'unrestricted', 'currency' => $data['currency'] ?? 'IDR', 'opening_balance' => $opening, 'current_balance' => $opening, 'available_balance' => $opening]);
            if (bccomp($opening, '0', 2) > 0) {
                $this->movement($fund, 'opening_balance', 'in', $opening, 'opening_balance', null, 'Opening balance');
            }
            $this->audit->record('fund_created', $fund);

            return $fund->fresh();
        });
    }

    public function find(string $id): Fund
    {
        return Fund::with(['movements' => fn ($q) => $q->latest(), 'allocations', 'reservations'])->find($id) ?? throw ZakatException::notFound('Fund tidak ditemukan.');
    }

    public function balance(Fund $fund): array
    {
        $this->refresh($fund);

        return $fund->fresh()->only(['current_balance', 'available_balance', 'reserved_balance', 'allocated_balance', 'distributed_balance']);
    }

    public function movements(Fund $fund, array $filters): LengthAwarePaginator
    {
        return $fund->movements()->when($filters['movement_type'] ?? null, fn ($q, $v) => $q->where('movement_type', $v))->when($filters['direction'] ?? null, fn ($q, $v) => $q->where('direction', $v))->latest('effective_at')->paginate(30);
    }

    public function inflow(Fund $fund, array $data): FundMovement
    {
        return DB::transaction(fn () => $this->movement($fund, 'collection_inflow', 'in', (string) $data['amount'], $data['source_type'] ?? 'collection', $data['source_id'] ?? null, $data['description'] ?? 'Fund inflow'));
    }

    public function inflowFromCollection(array $data): FundMovement
    {
        $collection = Collection::find($data['collection_id']) ?? throw ZakatException::notFound('Collection tidak ditemukan.');
        if ($collection->status?->value !== 'completed') {
            throw ZakatException::invalidTransition('Collection harus completed sebelum menjadi fund inflow.');
        }

        return $this->inflow($this->find($data['fund_id']), ['amount' => $collection->paid_amount, 'source_type' => 'collection', 'source_id' => $collection->id, 'description' => 'Collection '.$collection->collection_number]);
    }

    public function outflow(Fund $fund, array $data): FundMovement
    {
        return DB::transaction(function () use ($fund, $data) {
            $this->assertAvailable($fund, (string) $data['amount']);

            return $this->movement($fund, $data['movement_type'] ?? 'distribution', 'out', (string) $data['amount'], $data['source_type'] ?? 'distribution', $data['source_id'] ?? null, $data['description'] ?? 'Fund outflow');
        });
    }

    public function allocation(Fund $fund, array $data): FundAllocation
    {
        $this->assertAvailable($fund, (string) $data['amount']);

        return DB::transaction(function () use ($fund, $data) {
            $allocation = FundAllocation::create(['allocation_number' => app(BusinessNumberService::class)->next('ALC'), 'fund_id' => $fund->id, 'target_type' => $data['target_type'], 'target_id' => $data['target_id'] ?? null, 'amount' => $data['amount'], 'currency' => $fund->currency, 'status' => 'pending_approval', 'allocated_at' => now(), 'reason' => $data['reason'], 'created_by' => auth()->id()]);
            $this->refresh($fund);
            $this->audit->record('fund_allocation_created', $allocation);

            return $allocation;
        });
    }

    public function approveAllocation(FundAllocation $allocation): FundAllocation
    {
        if ($allocation->status !== 'pending_approval') {
            throw ZakatException::invalidTransition('Allocation tidak menunggu approval.');
        }
        if ($allocation->created_by && $allocation->created_by === auth()->id()) {
            throw ZakatException::forbidden('Maker tidak dapat menyetujui allocation sendiri.');
        }
        $fund = $allocation->fund;
        $this->assertAvailable($fund, (string) $allocation->amount);
        $allocation->forceFill(['status' => 'active', 'approved_by' => auth()->id(), 'approved_at' => now()])->saveQuietly();
        $this->refresh($fund);
        $this->audit->record('fund_allocation_approved', $allocation);

        return $allocation;
    }

    public function reservation(Fund $fund, array $data): FundReservation
    {
        return DB::transaction(function () use ($fund, $data) {
            $this->assertAvailable($fund, (string) $data['amount']);
            $reservation = FundReservation::create(['reservation_number' => app(BusinessNumberService::class)->next('RSV'), 'fund_id' => $fund->id, 'target_type' => $data['target_type'], 'target_id' => $data['target_id'] ?? null, 'amount' => $data['amount'], 'currency' => $fund->currency, 'status' => 'active', 'reserved_at' => now(), 'expires_at' => $data['expires_at'] ?? null, 'reason' => $data['reason'], 'created_by' => auth()->id()]);
            $this->refresh($fund);
            $this->audit->record('fund_reserved', $reservation);

            return $reservation;
        });
    }

    public function releaseReservation(FundReservation $reservation, string $reason): FundReservation
    {
        if ($reservation->status !== 'active') {
            throw ZakatException::invalidTransition('Reservation tidak aktif.');
        } $reservation->forceFill(['status' => 'released', 'released_at' => now()])->saveQuietly();
        $this->refresh($reservation->fund);
        $this->audit->record('fund_reservation_released', $reservation, context: ['reason' => $reason]);

        return $reservation;
    }

    public function transfer(array $data): FundTransfer
    {
        $source = $this->find($data['source_fund_id']);
        $destination = $this->find($data['destination_fund_id']);
        if ($source->id === $destination->id) {
            throw ZakatException::conflict('Fund sumber dan tujuan harus berbeda.');
        } $this->assertAvailable($source, (string) $data['amount']);

        return DB::transaction(fn () => FundTransfer::create(['transfer_number' => app(BusinessNumberService::class)->next('FTR'), 'source_fund_id' => $source->id, 'destination_fund_id' => $destination->id, 'amount' => $data['amount'], 'currency' => $source->currency, 'reason' => $data['reason'], 'status' => 'pending_approval', 'requested_by' => auth()->id()]));
    }

    public function approveTransfer(FundTransfer $transfer): FundTransfer
    {
        if ($transfer->status !== 'pending_approval') {
            throw ZakatException::invalidTransition('Transfer tidak menunggu approval.');
        } $source = $this->find($transfer->source_fund_id);
        $destination = $this->find($transfer->destination_fund_id);
        $this->assertAvailable($source, (string) $transfer->amount);

        return DB::transaction(function () use ($transfer, $source, $destination) {
            $this->movement($source, 'transfer_out', 'out', (string) $transfer->amount, 'fund_transfer', $transfer->id, $transfer->reason);
            $this->movement($destination, 'transfer_in', 'in', (string) $transfer->amount, 'fund_transfer', $transfer->id, $transfer->reason);
            $transfer->forceFill(['status' => 'completed', 'approved_by' => auth()->id(), 'transferred_at' => now()])->saveQuietly();
            $this->audit->record('fund_transfer_completed', $transfer);

            return $transfer;
        });
    }

    public function adjust(array $data): FundMovement
    {
        $fund = $this->find($data['fund_id']);
        if ($data['adjustment_type'] === 'decrease') {
            $this->assertAvailable($fund, (string) $data['amount']);
        }

        return DB::transaction(fn () => $this->movement($fund, 'adjustment', $data['adjustment_type'] === 'increase' ? 'in' : 'out', (string) $data['amount'], 'fund_adjustment', null, $data['reason']));
    }

    public function reconcile(Fund $fund, array $data): FundReconciliation
    {
        $system = (string) $fund->fresh()->current_balance;
        $external = (string) $data['external_balance'];
        $difference = bcsub($system, $external, 2);

        return FundReconciliation::create(['reconciliation_number' => app(BusinessNumberService::class)->next('REC'), 'fund_id' => $fund->id, 'reconciliation_date' => $data['reconciliation_date'] ?? now()->toDateString(), 'system_balance' => $system, 'external_balance' => $external, 'difference_amount' => $difference, 'status' => bccomp($difference, '0', 2) === 0 ? 'matched' : 'difference_found', 'notes' => $data['notes'] ?? null]);
    }

    public function availability(Fund $fund, string $amount): array
    {
        $this->refresh($fund);

        return ['available' => bccomp((string) $fund->available_balance, $amount, 2) >= 0, 'current_balance' => $fund->current_balance, 'available_balance' => $fund->available_balance, 'reserved_balance' => $fund->reserved_balance, 'allocated_balance' => $fund->allocated_balance, 'requested_amount' => $amount];
    }

    private function assertAvailable(Fund $fund, string $amount): void
    {
        $this->refresh($fund);
        if (bccomp((string) $fund->available_balance, $amount, 2) < 0) {
            throw ZakatException::conflict('INSUFFICIENT_FUND_BALANCE');
        }
    }

    private function movement(Fund $fund, string $type, string $direction, string $amount, ?string $sourceType, ?string $sourceId, string $description): FundMovement
    {
        $movement = FundMovement::create(['movement_number' => app(BusinessNumberService::class)->next('FND'), 'fund_id' => $fund->id, 'movement_type' => $type, 'direction' => $direction, 'amount' => $amount, 'currency' => $fund->currency, 'source_type' => $sourceType, 'source_id' => $sourceId, 'description' => $description, 'effective_at' => now(), 'created_by' => auth()->id()]);
        $this->refresh($fund);
        $this->audit->record('fund_movement_posted', $movement);

        return $movement;
    }

    private function refresh(Fund $fund): void
    {
        $in = (string) $fund->movements()->where('direction', 'in')->sum('amount');
        $out = (string) $fund->movements()->where('direction', 'out')->sum('amount');
        $reserved = (string) $fund->reservations()->where('status', 'active')->sum('amount');
        $allocated = (string) $fund->allocations()->whereIn('status', ['approved', 'active', 'partially_used'])->sum('amount');
        $distributed = (string) $fund->movements()->where('movement_type', 'distribution')->where('direction', 'out')->sum('amount');
        $current = bcsub($in, $out, 2);
        $available = bcsub(bcsub($current, $reserved, 2), $allocated, 2);
        if (bccomp($available, '0', 2) < 0) {
            $available = '0.00';
        } $fund->forceFill(['current_balance' => $current, 'reserved_balance' => $reserved, 'allocated_balance' => $allocated, 'distributed_balance' => $distributed, 'available_balance' => $available])->saveQuietly();
    }
}
