<?php

use App\Enums\JournalEntryStatus;
use App\Enums\JournalSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('reference', 50)->nullable();
            $table->string('description', 255);
            $table->string('source')->nullable();
            $table->string('status')->default(JournalEntryStatus::DRAFT->value);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['entry_date', 'status']);
            $table->index('reference');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
