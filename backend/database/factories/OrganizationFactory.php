<?php

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Organization> */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(8)),
            'name' => fake()->company(),
            'organization_type' => OrganizationType::Organization,
            'status' => OrganizationStatus::Active,
            'currency' => 'IDR',
            'timezone' => 'Asia/Makassar',
            'locale' => 'id',
        ];
    }

    /**
     * Factory mengisi kolom yang sengaja tidak fillable (status, organization_id).
     * Aman karena hanya dipakai untuk data uji, bukan untuk input dari client.
     */
    public function newModel(array $attributes = [])
    {
        $model = $this->modelName();

        return (new $model)->forceFill($attributes);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => OrganizationStatus::Suspended]);
    }
}
