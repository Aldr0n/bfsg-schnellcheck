<?php

namespace App\Models;

use App\Enums\AccessibilityRule;
use Database\Factories\FindingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $audit_id
 * @property AccessibilityRule $accessibility_rule
 * @property string $snippet
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['audit_id', 'accessibility_rule', 'snippet'])]
class Finding extends Model
{
    /**
     * @use HasFactory<FindingFactory>
     */
    use HasFactory;

    /**
     * @return BelongsTo<Audit, $this>
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accessibility_rule' => AccessibilityRule::class,
        ];
    }
}
