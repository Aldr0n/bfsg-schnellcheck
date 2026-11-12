<?php

namespace Database\Factories;

use App\Enums\AuditStatus;
use App\Models\Audit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Audit>
 */
class AuditFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => fake()->url(),
            'status' => AuditStatus::Pending,
        ];
    }
}
