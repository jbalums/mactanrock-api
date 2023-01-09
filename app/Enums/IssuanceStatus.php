<?php

namespace App\Enums;

enum IssuanceStatus: string
{
    case Pending = "pending";
    case Approved = "approved";
    case Accepted = "accepted";
    case Cancelled = "cancelled";
    case Returned = "returned";
}