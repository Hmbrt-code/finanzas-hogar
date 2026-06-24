<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'member_id', 'category_id', 'type', 'amount',
        'description', 'date', 'status', 'is_recurring', 'recurrence_frequency',
    ];

    protected $casts = [
        'date'         => 'date',
        'amount'       => 'decimal:2',
        'is_recurring' => 'boolean',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOfMonth($query, int $year, int $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    public function scopeOfYear($query, int $year)
    {
        return $query->whereYear('date', $year);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
