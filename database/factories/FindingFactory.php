<?php

namespace Database\Factories;

use App\Enums\AccessibilityRule;
use App\Models\Audit;
use App\Models\Finding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Finding>
 */
class FindingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audit_id' => Audit::factory(),
            'accessibility_rule' => AccessibilityRule::ImageAlt,
            'snippet' => '<img src="logo.png">',
        ];
    }
}
