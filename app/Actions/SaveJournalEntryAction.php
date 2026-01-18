<?php

namespace App\Actions;

use App\Enums\JournalEntryStatus;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;

class SaveJournalEntryAction
{
    public function execute(array $data, ?JournalEntry $journalEntry = null): JournalEntry
    {
        return DB::transaction(function () use ($data, $journalEntry) {
            if ($journalEntry && $journalEntry->status !== JournalEntryStatus::DRAFT) {
                throw new \Exception('Cannot edit posted or reversed entries');
            }

            $entryData = [
                'entry_date' => $data['entry_date'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'],
                'source' => $data['source'] ?? null,
            ];

            if ($journalEntry) {
                $journalEntry->update($entryData);
            } else {
                $entryData['created_by'] = auth()->id();
                $entryData['status'] = JournalEntryStatus::DRAFT;
                $journalEntry = JournalEntry::create($entryData);
            }

            if (isset($data['lines']) && is_array($data['lines'])) {
                $journalEntry->journalLines()->delete();

                foreach ($data['lines'] as $index => $line) {
                    if (empty($line['account_id'])) {
                        continue;
                    }

                    JournalLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_id' => $line['account_id'],
                        'line_no' => $index + 1,
                        'narration' => $line['narration'] ?? null,
                        'debit' => $line['debit'] ?? 0,
                        'credit' => $line['credit'] ?? 0,
                    ]);
                }
            }

            return $journalEntry->fresh(['journalLines.account']);
        });
    }
}
