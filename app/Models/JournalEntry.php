<?php

namespace App\Models;

use App\Enums\JournalEntryStatus;
use App\Enums\JournalSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'entry_date',
        'reference',
        'description',
        'source',
        'status',
        'posted_at',
        'created_by',
        'posted_by',
        'reversed_entry_id',
    ];

    protected $appends = ['total_debit', 'total_credit'];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'posted_at' => 'datetime',
            'status' => JournalEntryStatus::class,
            'source' => JournalSource::class,
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reversedEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversed_entry_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(JournalEntry::class, 'reversed_entry_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function getTotalDebitAttribute(): float
    {
        if (!$this->relationLoaded('journalLines')) {
            return 0;
        }
        return (float) $this->journalLines->sum('debit');
    }

    public function getTotalCreditAttribute(): float
    {
        if (!$this->relationLoaded('journalLines')) {
            return 0;
        }
        return (float) $this->journalLines->sum('credit');
    }

    public function getIsBalancedAttribute(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }
}
