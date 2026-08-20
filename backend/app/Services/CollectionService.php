<?php

namespace App\Services;

use App\Enums\CollectionPaymentStatus;
use App\Enums\CollectionSource;
use App\Enums\CollectionStatus;
use App\Exceptions\ZakatException;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\CollectionPayment;
use App\Models\Muzaki;
use App\Models\PaymentAllocation;
use App\Models\ZakatCalculation;
use App\Models\ZakatType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CollectionService
{
    public function __construct(private readonly AuditService $audit) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $this->expireDueCollections();

        return Collection::with(['muzaki:id,display_name,business_number', 'type:id,code,name'])->when($filters['muzaki_id'] ?? null, fn ($q, $v) => $q->where('muzaki_id', $v))->when($filters['zakat_type_id'] ?? null, fn ($q, $v) => $q->where('zakat_type_id', $v))->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($filters['source'] ?? null, fn ($q, $v) => $q->where('source', $v))->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('collection_date', '>=', $v))->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('collection_date', '<=', $v))->latest()->paginate(min((int) ($filters['per_page'] ?? 15), (int) config('zakat.pagination.max_per_page')));
    }

    public function create(array $data): Collection
    {
        $muzaki = Muzaki::find($data['muzaki_id']) ?? throw ZakatException::notFound('Muzaki tidak ditemukan.');
        $type = ZakatType::find($data['zakat_type_id']) ?? throw ZakatException::notFound('Jenis zakat tidak ditemukan.');
        $source = $data['source'] ?? CollectionSource::Manual->value;

        return DB::transaction(function () use ($data, $muzaki, $type, $source) {
            $collection = Collection::create(['collection_number' => app(BusinessNumberService::class)->next('COL'), 'muzaki_id' => $muzaki->id, 'zakat_type_id' => $type->id, 'collection_date' => $data['collection_date'] ?? now()->toDateString(), 'due_date' => $data['due_date'] ?? null, 'status' => CollectionStatus::Draft, 'currency' => $data['currency'] ?? 'IDR', 'expected_amount' => $data['expected_amount'], 'remaining_amount' => $data['expected_amount'], 'source' => $source, 'notes' => $data['notes'] ?? null, 'source_snapshot' => ['reason' => $data['reason'] ?? null]]);
            $this->item($collection, $type, $data['expected_amount'], null, 'Zakat');
            $this->audit->record('collection_created', $collection);

            return $this->find($collection->id);
        });
    }

    public function fromCalculation(array $data): Collection
    {
        $calculation = ZakatCalculation::with(['snapshot', 'type', 'rule'])->find($data['calculation_id']) ?? throw ZakatException::notFound('Calculation tidak ditemukan.');
        if ($calculation->status?->value !== 'confirmed') {
            throw ZakatException::invalidTransition('Hanya calculation confirmed yang dapat dibuat menjadi collection.');
        }
        if ($calculation->eligibility_status?->value !== 'eligible') {
            throw ZakatException::conflict('Calculation belum eligible.');
        }
        $collection = $this->create(['muzaki_id' => $calculation->muzaki_id, 'zakat_type_id' => $calculation->zakat_type_id, 'expected_amount' => $calculation->zakat_amount, 'currency' => $calculation->currency, 'collection_date' => now()->toDateString(), 'due_date' => $data['due_date'] ?? null, 'source' => CollectionSource::Calculator->value, 'calculation_id' => $calculation->id, 'notes' => $data['notes'] ?? null]);
        $collection->forceFill(['calculation_id' => $calculation->id, 'zakat_rule_id' => $calculation->zakat_rule_id, 'source_snapshot' => ['calculation_number' => $calculation->calculation_number, 'zakat_amount' => $calculation->zakat_amount, 'currency' => $calculation->currency, 'snapshot_id' => $calculation->snapshot?->id]])->saveQuietly();

        return $this->find($collection->id);
    }

    public function find(string $id): Collection
    {
        return Collection::with(['muzaki', 'type', 'items', 'payments.allocations', 'allocations'])->find($id) ?? throw ZakatException::notFound('Collection tidak ditemukan.');
    }

    public function confirm(Collection $collection): Collection
    {
        if ($collection->status !== CollectionStatus::Draft) {
            throw ZakatException::invalidTransition('Hanya collection draft yang dapat dikonfirmasi.');
        } $collection->forceFill(['status' => CollectionStatus::Pending])->saveQuietly();
        $this->audit->record('collection_confirmed', $collection);

        return $collection;
    }

    public function cancel(Collection $collection, string $reason): Collection
    {
        if (in_array($collection->status, [CollectionStatus::Completed, CollectionStatus::Cancelled, CollectionStatus::Refunded], true) || $collection->paid_amount > 0) {
            throw ZakatException::invalidTransition('Collection sudah memiliki pembayaran atau tidak dapat dibatalkan.');
        } $collection->forceFill(['status' => CollectionStatus::Cancelled, 'cancellation_reason' => $reason])->saveQuietly();
        $this->audit->record('collection_cancelled', $collection, context: ['reason' => $reason]);

        return $collection;
    }

    public function reactivate(Collection $collection): Collection
    {
        if ($collection->status !== CollectionStatus::Expired) {
            throw ZakatException::invalidTransition('Hanya collection expired yang dapat diaktifkan kembali.');
        } $collection->forceFill(['status' => CollectionStatus::Pending])->saveQuietly();
        $this->audit->record('collection_reactivated', $collection);

        return $collection;
    }

    public function payment(Collection $collection, array $data): CollectionPayment
    {
        if (! in_array($collection->status, [CollectionStatus::Pending, CollectionStatus::PartiallyPaid, CollectionStatus::Paid], true)) {
            throw ZakatException::invalidTransition('Collection tidak menerima pembayaran pada status ini.');
        }

return CollectionPayment::create(['collection_id' => $collection->id, 'payment_reference' => $data['payment_reference'], 'status' => CollectionPaymentStatus::Pending, 'payment_method' => $data['payment_method'], 'payment_instrument' => $data['payment_instrument'] ?? null, 'amount' => $data['amount'], 'currency' => $data['currency'] ?? $collection->currency, 'payment_date' => $data['payment_date'] ?? now(), 'metadata' => $data['metadata'] ?? null]);
    }

    public function verifyPayment(CollectionPayment $payment, string $status): Collection
    {
        if ($status === CollectionPaymentStatus::Settled->value) {
            $payment->forceFill(['status' => CollectionPaymentStatus::Settled, 'verified_at' => now(), 'verified_by' => auth()->id()])->saveQuietly();

            return $this->settle($payment);
        } $payment->forceFill(['status' => $status, 'verified_at' => now(), 'verified_by' => auth()->id()])->saveQuietly();

        return $this->find($payment->collection_id);
    }

    public function summary(): array
    {
        $this->expireDueCollections();
        $q = Collection::query();

        return ['total_collections' => (clone $q)->count(), 'total_expected' => (string) (clone $q)->sum('expected_amount'), 'total_paid' => (string) (clone $q)->sum('paid_amount'), 'total_remaining' => (string) (clone $q)->sum('remaining_amount'), 'pending_count' => (clone $q)->where('status', CollectionStatus::Pending)->count(), 'partially_paid_count' => (clone $q)->where('status', CollectionStatus::PartiallyPaid)->count(), 'completed_count' => (clone $q)->where('status', CollectionStatus::Completed)->count()];
    }

    private function settle(CollectionPayment $payment): Collection
    {
        return DB::transaction(function () use ($payment) {
            $collection = Collection::lockForUpdate()->findOrFail($payment->collection_id);
            $already = (float) PaymentAllocation::where('payment_id', $payment->id)->sum('allocated_amount');
            $available = (float) $payment->amount - $already;
            $remaining = max(0, (float) $collection->expected_amount - (float) $collection->paid_amount);
            $allocation = min($available, $remaining);
            if ($allocation > 0) {
                PaymentAllocation::create(['payment_id' => $payment->id, 'collection_id' => $collection->id, 'collection_item_id' => $collection->items()->value('id'), 'allocated_amount' => $allocation, 'currency' => $collection->currency]);
            } $paid = (float) $collection->paid_amount + $allocation;
            $status = $paid > (float) $collection->expected_amount ? CollectionStatus::Paid : ($paid >= (float) $collection->expected_amount ? CollectionStatus::Paid : ($paid > 0 ? CollectionStatus::PartiallyPaid : CollectionStatus::Pending));
            $collection->forceFill(['paid_amount' => $paid, 'remaining_amount' => max(0, (float) $collection->expected_amount - $paid), 'payment_count' => $collection->payments()->whereIn('status', [CollectionPaymentStatus::Verified, CollectionPaymentStatus::Settled])->count(), 'status' => $status, 'overpayment_status' => $available > $allocation ? 'detected' : 'none'])->saveQuietly();
            $collection->items()->update(['paid_amount' => $paid, 'remaining_amount' => max(0, (float) $collection->expected_amount - $paid), 'status' => $status->value]);
            $this->audit->record('collection_payment_received', $collection, context: ['payment_id' => $payment->id, 'allocated_amount' => $allocation]);
            if ($status === CollectionStatus::Paid) {
                $collection->forceFill(['status' => CollectionStatus::Completed])->saveQuietly();
                $this->audit->record('collection_completed', $collection);
            }

return $this->find($collection->id);
        });
    }

    private function item(Collection $collection, ZakatType $type, float|string $amount, ?string $calculationId, string $description): void
    {
        CollectionItem::create(['collection_id' => $collection->id, 'zakat_type_id' => $type->id, 'calculation_id' => $calculationId, 'description' => $description, 'expected_amount' => $amount, 'remaining_amount' => $amount]);
    }

    private function expireDueCollections(): void
    {
        Collection::whereIn('status', [CollectionStatus::Draft, CollectionStatus::Pending, CollectionStatus::PartiallyPaid])->whereDate('due_date', '<', today())->whereColumn('remaining_amount', '>', '0')->update(['status' => CollectionStatus::Expired, 'updated_at' => now()]);
    }
}
