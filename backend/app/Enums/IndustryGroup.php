<?php

namespace App\Enums;

/**
 * Clusters used to group the 33 industries on the onboarding and explore screens.
 */
enum IndustryGroup: string
{
    case FinanceInvestment = 'finance_investment';
    case DeepTech = 'deep_tech';
    case DigitalSoftware = 'digital_software';
    case Sectors = 'sectors';
    case SocietyGov = 'society_gov';
}
