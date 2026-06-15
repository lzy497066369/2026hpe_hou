<?php

namespace App\Enums;

enum PrizeClaimType: string
{
    case Shipping = 'shipping';
    case Pickup = 'pickup';
}
