<?php

namespace App\Actions;

use App\Enums\JournalEntryStatus;
use App\Enums\JournalSource;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class ReverseJournalEntryAction
{
    public function execute(JournalEntry $journalEntry): JournalEntry
    {
        return DB::transaction(function () use ($journalEntry) {
            if ($journalEntry->status !== JournalEntryStatus::POSTED) {
                throw new \Exception('Only posted entries can be reversed');
            }

            $journalEntry->load('journalLines');

            $reversal = JournalEntry::create([
                'entry_date' => now()->toDateString(),
                'description' => "Reversal of entry #{$journalEntry->id}",
                'source' => JournalSource::ADJUSTMENT,
                'status' => JournalEntryStatus::POSTED,
                'posted_at' => now(),
                'created_by' => auth()->id(),
                'posted_by' => auth()->id(),
            ]);

            foreach ($journalEntry->journalLines as $line) {
                $reversal->journalLines()->create([
                    'account_id' => $line->account_id,
                    'line_no' => $line->line_no,
                    'narration' => $line->narration,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                ]);
            }

            $journalEntry->update([
                'status' => JournalEntryStatus::REVERSED,
                'reversed_entry_id' => $reversal->id,
            ]);

            return $reversal->fresh(['journalLines.account']);
        });
    }
}
