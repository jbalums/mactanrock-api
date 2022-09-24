<?php

namespace App\Enums;

enum ReceivingStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}