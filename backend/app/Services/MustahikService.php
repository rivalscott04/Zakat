<?php

namespace App\Services;

use App\Exceptions\ZakatException;
use App\Models\Mustahik;
use App\Models\MustahikAddress;
use App\Models\MustahikAsnaf;
use App\Models\MustahikIdentity;
use App\Models\MustahikProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MustahikService
{
    public function __construct(private readonly AuditService $audit) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return Mustahik::with(['asnaf' => fn ($q) => $q->where('status', 'active'), 'addresses' => fn ($q) => $q->where('is_primary', true)])->when($filters['search'] ?? null, fn ($q, $v) => $q->where(fn ($x) => $x->where('display_name', 'ilike', "%{$v}%")->orWhere('mustahik_number', 'ilike', "%{$v}%")))->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($filters['verification_status'] ?? null, fn ($q, $v) => $q->where('verification_status', $v))->latest()->paginate(min((int) ($filters['per_page'] ?? 15), (int) config('zakat.pagination.max_per_page')));
    }

    public function create(array $data): Mustahik
    {
        $identity = $data['identity_number'] ?? null;
        $duplicate = $this->duplicates($data);
        if ($duplicate !== []) {
            throw ZakatException::conflict('MUSTAHIK_POSSIBLE_DUPLICATE', ['matches' => $duplicate]);
        }

        return DB::transaction(function () use ($data, $identity) {
            $mustahik = Mustahik::create(['mustahik_number' => app(BusinessNumberService::class)->next('MSH'), 'mustahik_type' => $data['mustahik_type'] ?? 'individual', 'full_name' => $data['full_name'], 'display_name' => $data['display_name'] ?? $data['full_name'], 'gender' => $data['gender'] ?? null, 'birth_date' => $data['birth_date'] ?? null, 'marital_status' => $data['marital_status'] ?? null, 'phone' => $data['phone'] ?? null, 'email' => $data['email'] ?? null, 'identity_type' => $data['identity_type'] ?? null, 'identity_number_hash' => $identity ? hash('sha256', $identity) : null, 'registered_at' => now()->toDateString(), 'registered_by' => auth()->id()]);
            if ($identity) {
                $this->identity($mustahik, ['identity_type' => $data['identity_type'] ?? 'other', 'identity_number' => $identity, 'identity_name' => $data['full_name']]);
            } if (! empty($data['address'])) {
                $this->address($mustahik, $data['address']);
            } if (! empty($data['profile'])) {
                MustahikProfile::create($data['profile'] + ['mustahik_id' => $mustahik->id]);
            } $this->audit->record('mustahik_created', $mustahik);

            return $this->find($mustahik->id);
        });
    }

    public function find(string $id): Mustahik
    {
        return Mustahik::with(['identities', 'addresses', 'asnaf', 'profile'])->find($id) ?? throw ZakatException::notFound('Mustahik tidak ditemukan.');
    }

    public function update(Mustahik $mustahik, array $data): Mustahik
    {
        $mustahik->fill($data)->save();
        $this->audit->record('mustahik_updated', $mustahik);

        return $this->find($mustahik->id);
    }

    public function duplicateCheck(array $data): array
    {
        return $this->duplicates($data);
    }

    public function identity(Mustahik $mustahik, array $data): MustahikIdentity
    {
        $hash = hash('sha256', $data['identity_number']);

        // F-08 — pengecekan duplikat dibatasi ke organisasi pemilik mustahik.
        // Tanpa batasan ini, organisasi lain bisa memakai pesan duplikat untuk
        // memastikan sebuah NIK sudah terdaftar di tempat lain.
        $duplicate = MustahikIdentity::query()
            ->acrossOrganizations()
            ->where('organization_id', $mustahik->organization_id)
            ->where('identity_number_hash', $hash)
            ->where('mustahik_id', '!=', $mustahik->id)
            ->exists();

        if ($duplicate) {
            throw ZakatException::conflict('IDENTITY_DUPLICATE');
        }

        $identity = new MustahikIdentity;
        $identity->fill(['mustahik_id' => $mustahik->id, 'identity_type' => $data['identity_type'], 'identity_number_encrypted' => $data['identity_number'], 'identity_number_hash' => $hash, 'identity_name' => $data['identity_name'] ?? null]);
        $identity->organization_id = $mustahik->organization_id;
        $identity->save();

        return $identity;
    }

    public function address(Mustahik $mustahik, array $data): MustahikAddress
    {
        if (($data['is_primary'] ?? false) === true) {
            $mustahik->addresses()->update(['is_primary' => false]);
        }

        return MustahikAddress::create($data + ['mustahik_id' => $mustahik->id, 'address_type' => $data['address_type'] ?? 'home']);
    }

    public function asnaf(Mustahik $mustahik, array $data): MustahikAsnaf
    {
        if (($data['primary_asnaf'] ?? false) === true) {
            $mustahik->asnaf()->where('status', 'active')->update(['primary_asnaf' => false]);
        } $asnaf = MustahikAsnaf::create($data + ['mustahik_id' => $mustahik->id, 'effective_from' => $data['effective_from'] ?? now()->toDateString(), 'assigned_by' => auth()->id()]);
        $this->audit->record('mustahik_asnaf_assigned', $asnaf);

        return $asnaf;
    }

    public function verify(Mustahik $mustahik, string $status): Mustahik
    {
        $mustahik->forceFill(['verification_status' => $status])->saveQuietly();
        $this->audit->record('mustahik_verified', $mustahik, context: ['status' => $status]);

        return $mustahik;
    }

    private function duplicates(array $data): array
    {
        $query = Mustahik::query()->where(fn ($q) => $q->where('display_name', 'ilike', $data['full_name'])->when($data['phone'] ?? null, fn ($x, $v) => $x->orWhere('phone', $v))->when($data['birth_date'] ?? null, fn ($x, $v) => $x->orWhereDate('birth_date', $v)));
        if (! empty($data['identity_number'])) {
            $query->orWhere('identity_number_hash', hash('sha256', $data['identity_number']));
        }

        return $query->limit(10)->get(['id', 'mustahik_number', 'display_name', 'verification_status'])->toArray();
    }
}
