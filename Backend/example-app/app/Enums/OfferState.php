<?php

namespace App\Enums;

enum OfferState: string
{
    case ACTIVE = "active";
    case VERIFIYING = "verifying";
    case PURCHASED = "purchased";
    case INACTIVE = "inactive";
}
