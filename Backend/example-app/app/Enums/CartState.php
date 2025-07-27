<?php

namespace App\Enums;

enum CartState: string
{
    case ACTIVE = "active";
    case PURCHASED = "purchased";
}
