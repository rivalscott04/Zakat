<?php

namespace App\Enums;

enum CalculationMethod: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
    case NisabBased = 'nisab_based';
    case AssetBased = 'asset_based';
    case IncomeBased = 'income_based';
    case HarvestBased = 'harvest_based';
    case LivestockBased = 'livestock_based';
    case Custom = 'custom';
}
