<?php

namespace App\Enums;

enum InventoryType: string
{
    case PARTS = 'parts';
    case SERVICE = 'service';
}