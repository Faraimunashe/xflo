<?php

namespace App\Http\Controllers;

use App\Actions\PostJournalEntryAction;
use App\Actions\ReverseJournalEntryAction;
use App\Actions\SaveJournalEntryAction;
use App\Enums\JournalEntryStatus;
use App\Enums\JournalSource;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JournalEntryController extends Controller
{
    public function __construct(
        private SaveJournalEntryAction $saveJournalEntryAction,
        private PostJournalEntryAction $postJournalEntryAction,
        private ReverseJournalEntryAction $reverseJournalEntryAction,
    ) {}

    public function index(Request $request): Response
    {
        $query = JournalEntry::query()->with(['createdBy', 'postedBy', 'reversal', 'journalLines']);

        if ($request->has('date_from')) {
            $query->where('entry_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('entry_date', '<=', $request->date_to);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('source')) {
            $query->where('source', $request->source);
        }

        if ($request->has('reference')) {
            $query->where('reference', 'like', "%{$request->reference}%");
        }

        $entries = $query->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return Inertia::render('JournalEntries/Index', [
            'entries' => $entries,
            'filters' => $request->only(['date_from', 'date_to', 'status', 'source', 'reference']),
            'statuses' => array_map(fn($case) => ['value' => $case->value, 'label' => ucfirst($case->value)], JournalEntryStatus::cases()),
            'sources' => array_map(fn($case) => ['value' => $case->value, 'label' => ucfirst(str_replace('_', ' ', $case->value))], JournalSource::cases()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('JournalEntries/Create', [
            'sources' => array_map(fn($case) => ['value' => $case->value, 'label' => ucfirst(str_replace('_', ' ', $case->value))], JournalSource::cases()),
            'accounts' => Account::where('is_active', true)
                ->orderBy('code')
                ->get()
                ->map(fn($account) => [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'label' => "{$account->code} - {$account->name}",
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'reference' => 'nullable|string|max:50',
            'description' => 'required|string|max:255',
            'source' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.narration' => 'nullable|string|max:255',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        $journalEntry = $this->saveJournalEntryAction->execute($validated);

        return redirect()->route('journal-entries.show', $journalEntry)->with('success', 'Journal entry saved as draft.');
    }

    public function show(JournalEntry $journalEntry): Response
    {
        $journalEntry->load(['journalLines.account', 'createdBy', 'postedBy', 'reversal']);
        return Inertia::render('JournalEntries/Show', [
            'entry' => $journalEntry,
        ]);
    }

    public function edit(JournalEntry $journalEntry): Response
    {
        if ($journalEntry->status !== JournalEntryStatus::DRAFT) {
            return redirect()->route('journal-entries.show', $journalEntry)
                ->with('error', 'Cannot edit posted or reversed entries.');
        }

        $journalEntry->load(['journalLines.account']);

        return Inertia::render('JournalEntries/Edit', [
            'entry' => $journalEntry,
            'sources' => array_map(fn($case) => ['value' => $case->value, 'label' => ucfirst(str_replace('_', ' ', $case->value))], JournalSource::cases()),
            'accounts' => Account::where('is_active', true)
                ->orderBy('code')
                ->get()
                ->map(fn($account) => [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'label' => "{$account->code} - {$account->name}",
                ]),
        ]);
    }

    public function update(Request $request, JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== JournalEntryStatus::DRAFT) {
            return back()->with('error', 'Cannot edit posted or reversed entries.');
        }

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'reference' => 'nullable|string|max:50',
            'description' => 'required|string|max:255',
            'source' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.narration' => 'nullable|string|max:255',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);

        $journalEntry = $this->saveJournalEntryAction->execute($validated, $journalEntry);

        return redirect()->route('journal-entries.show', $journalEntry)->with('success', 'Journal entry updated.');
    }

    public function post(Request $request, JournalEntry $journalEntry)
    {
        try {
            $this->postJournalEntryAction->execute($journalEntry);
            return redirect()->route('journal-entries.show', $journalEntry)
                ->with('success', 'Journal entry posted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reverse(Request $request, JournalEntry $journalEntry)
    {
        try {
            $reversal = $this->reverseJournalEntryAction->execute($journalEntry);
            return redirect()->route('journal-entries.show', $reversal)
                ->with('success', 'Journal entry reversed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(JournalEntry $journalEntry)
    {
        try {
            // Prevent deletion if this entry has a reversal entry (i.e., has been reversed)
            if ($journalEntry->reversal()->exists()) {
                return back()->with('error', 'Cannot delete entry that has been reversed. Delete the reversal entry first.');
            }

            // Prevent deletion if this entry is a reversal of another entry
            // (i.e., another entry's reversed_entry_id points to this entry)
            if ($journalEntry->reversed_entry_id !== null) {
                return back()->with('error', 'Cannot delete reversal entry. Delete the original entry first.');
            }

            $journalEntry->delete();

            return redirect()->route('journal-entries.index')
                ->with('success', 'Journal entry deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete journal entry: ' . $e->getMessage());
        }
    }
}
