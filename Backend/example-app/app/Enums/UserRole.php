<?php

namespace App\Enums;

enum UserRole: string
{
    case DEFAULT = "default";
    case ADMIN = "admin";
    case CUSTOMER = "customer";
    case SELLER = "seller";

}
