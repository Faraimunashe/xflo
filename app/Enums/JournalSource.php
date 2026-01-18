<?php

namespace App\Enums;

enum JournalSource: string
{
    case TUITION = 'tuition';
    case LEVY = 'levy';
    case EXPENSE = 'expense';
    case BANK = 'bank';
    case OPENING_BALANCE = 'opening_balance';
    case ADJUSTMENT = 'adjustment';
}
