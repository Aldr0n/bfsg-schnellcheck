<?php

namespace App\Models;

use App\Enums\AuditStatus;
use Database\Factories\AuditFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $url
 * @property AuditStatus $status
 * @property string|null $error_message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['url', 'status', 'error_message'])]
class Audit extends Model
{
    /**
     * @use HasFactory<AuditFactory>
     */
    use HasFactory, HasUlids;

    /**
     * @return HasMany<Finding, $this>
     */
    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AuditStatus::class,
        ];
    }
}
