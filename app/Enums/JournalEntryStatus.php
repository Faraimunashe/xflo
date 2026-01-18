<?php

namespace App\Enums;

enum JournalEntryStatus: string
{
    case DRAFT = 'draft';
    case POSTED = 'posted';
    case REVERSED = 'reversed';
}
