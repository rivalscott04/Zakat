<?php

namespace App\Services;

use App\Enums\MuzakiStatus;
use App\Enums\MuzakiType;
use App\Exceptions\ZakatException;
use App\Models\Muzaki;
use App\Models\MuzakiIndividualProfile;
use App\Models\MuzakiOrganizationProfile;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MuzakiService
{
    public function __construct(private readonly AuditService $audit) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        return Muzaki::query()->with(['individualProfile', 'organizationProfile'])
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($x) => $x->where('display_name', 'ilike', "%{$s}%")->orWhere('business_number', 'ilike', "%{$s}%")))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('muzaki_type', $v))
            ->orderBy('display_name')->paginate(min((int) ($filters['per_page'] ?? config('zakat.pagination.per_page')), (int) config('zakat.pagination.max_per_page')));
    }

    public function findInContext(string $id): Muzaki
    {
        return Muzaki::with(['individualProfile', 'organizationProfile', 'contacts', 'addresses', 'preference'])->find($id)
            ?? throw ZakatException::notFound('Muzaki tidak ditemukan.');
    }

    public function create(array $data): Muzaki
    {
        $type = MuzakiType::from($data['muzaki_type']);
        $profile = $data['profile'] ?? [];

        return DB::transaction(function () use ($data, $type, $profile) {
            $muzaki = new Muzaki;
            $muzaki->fill(['display_name' => $data['display_name'], 'registration_source' => $data['registration_source'] ?? 'manual', 'registered_at' => now()]);
            $muzaki->muzaki_type = $type;
            $muzaki->status = MuzakiStatus::Lead;
            $muzaki->organization_id = OrganizationContext::requireId();
            $muzaki->save();
            if ($type === MuzakiType::Individual) {
                (new MuzakiIndividualProfile)->fill(array_merge(['full_name' => $data['display_name']], $profile))->forceFill(['muzaki_id' => $muzaki->id])->save();
            } elseif ($type->usesOrganizationProfile()) {
                (new MuzakiOrganizationProfile)->fill(array_merge(['legal_name' => $data['display_name']], $profile))->forceFill(['muzaki_id' => $muzaki->id])->save();
            }
            $this->audit->record('muzaki_created', $muzaki);

            return $this->findInContext($muzaki->id);
        });
    }

    public function update(Muzaki $muzaki, array $data): Muzaki
    {
        $before = $muzaki->only(['display_name', 'registration_source']);
        $muzaki->fill($data)->save();
        $this->audit->record('muzaki_updated', $muzaki, $before, $muzaki->only(['display_name', 'registration_source']));

        return $this->findInContext($muzaki->id);
    }

    public function changeStatus(Muzaki $muzaki, MuzakiStatus $status): Muzaki
    {
        if ($muzaki->status === MuzakiStatus::Archived && $status !== MuzakiStatus::Archived) {
            throw ZakatException::invalidTransition('Muzaki archived tidak dapat diaktifkan kembali.');
        }
        $previous = $muzaki->status;
        $muzaki->status = $status;
        $muzaki->saveQuietly();
        $this->audit->record('muzaki_status_changed', $muzaki, ['status' => $previous->value], ['status' => $status->value]);

        return $muzaki;
    }

    public function summary(Muzaki $muzaki): array
    {
        return ['available' => false, 'reason' => 'Collection dan Ledger belum tersedia.', 'total_contributions' => '0.00', 'contribution_count' => 0];
    }
}
