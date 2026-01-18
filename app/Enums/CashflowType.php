<?php

namespace App\Enums;

enum CashflowType: string
{
    case OPERATING = 'operating';
    case INVESTING = 'investing';
    case FINANCING = 'financing';
}
