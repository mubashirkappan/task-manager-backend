<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    /**
     * Scope for searching title
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function ($q, $search) {
            $q->where('title', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for filtering status
     */
    public function scopeFilterStatus(Builder $query, ?string $status): Builder
    {
        return $query->when($status, function ($q, $status) {
            $q->where('status', $status);
        });
    }
}
