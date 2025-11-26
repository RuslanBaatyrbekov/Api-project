<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case CREATED = 'created';
    case PROCESSING = 'processing';
    case PAID = 'paid';
    case FAILED = 'failed';
}
