<?php

namespace App;

enum FeeFrequency: string
{
    case Monthly = 'monthly';
    case Biannual = 'biannual';
    case Annual = 'annual';
}