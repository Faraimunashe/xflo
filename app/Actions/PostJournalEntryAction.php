<?php

namespace App\Actions;

use App\Enums\JournalEntryStatus;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class PostJournalEntryAction
{
    public function execute(JournalEntry $journalEntry): JournalEntry
    {
        return DB::transaction(function () use ($journalEntry) {
            if ($journalEntry->status !== JournalEntryStatus::DRAFT) {
                throw new \Exception('Only draft entries can be posted');
            }

            $journalEntry->load('journalLines');

            if ($journalEntry->journalLines->count() < 2) {
                throw new \Exception('Journal entry must have at least 2 lines');
            }

            foreach ($journalEntry->journalLines as $line) {
                if (empty($line->account_id)) {
                    throw new \Exception('All lines must have an account');
                }

                if ($line->debit > 0 && $line->credit > 0) {
                    throw new \Exception('Each line must have either debit or credit, not both');
                }

                if ($line->debit == 0 && $line->credit == 0) {
                    throw new \Exception('Each line must have either debit or credit');
                }
            }

            $totalDebit = $journalEntry->journalLines->sum('debit');
            $totalCredit = $journalEntry->journalLines->sum('credit');

            if (abs($totalDebit - $totalCredit) >= 0.01) {
                throw new \Exception('Total debits must equal total credits');
            }

            $journalEntry->update([
                'status' => JournalEntryStatus::POSTED,
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);

            return $journalEntry->fresh(['journalLines.account']);
        });
    }
}
