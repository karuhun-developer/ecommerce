<?php

namespace App\Enums;

enum PaymentGatewayDriver: string
{
    case Midtrans = 'midtrans';
    case Paywuz = 'paywuz';
    case KaruhunQris = 'karuhunqris';
}
