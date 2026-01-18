<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\CashflowType;
use App\Enums\NormalBalance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'normal_balance',
        'is_cash',
        'cashflow_type',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'normal_balance' => NormalBalance::class,
            'cashflow_type' => CashflowType::class,
            'is_cash' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
