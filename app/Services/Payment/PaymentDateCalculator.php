<?php

namespace App\Services\Payment;

use App\Enums\SalaryPaymentTypes;
use Carbon\Carbon;

class PaymentDateCalculator
{
    public static function getNextPaymentDate(int $salaryPaymentType): Carbon
    {
        $today = Carbon::now();

        return match ($salaryPaymentType) {
            SalaryPaymentTypes::MONTHLY => self::calculateMonthlyPaymentDate($today),
            SalaryPaymentTypes::WEEKLY => self::calculateWeeklyPaymentDate($today),
            SalaryPaymentTypes::BI_WEEKLY => self::calculateBiWeeklyPaymentDate($today),
            default => throw new \InvalidArgumentException('Invalid salary payment type')
        };
    }

    public static function getNextNextPaymentDate(int $salaryPaymentType): Carbon
    {
        $currentPaymentDate = self::getNextPaymentDate($salaryPaymentType);

        return match ($salaryPaymentType) {
            SalaryPaymentTypes::MONTHLY => self::calculateMonthlyPaymentDate($currentPaymentDate->addDay()),
            SalaryPaymentTypes::WEEKLY => self::calculateWeeklyPaymentDate($currentPaymentDate->addDay()),
            SalaryPaymentTypes::BI_WEEKLY => self::calculateBiWeeklyPaymentDate($currentPaymentDate->addDay()),
            default => throw new \InvalidArgumentException('Invalid salary payment type')
        };
    }
    private static function calculateMonthlyPaymentDate(Carbon $date): Carbon
    {
        return $date->copy()->endOfMonth();
    }

    private static function calculateWeeklyPaymentDate(Carbon $date): Carbon
    {
        return $date->copy()->endOfWeek();
    }

    private static function calculateBiWeeklyPaymentDate(Carbon $date): Carbon
    {
        // If we're before the 15th, next payment is on the 15th
        if ($date->day < 15) {
            return $date->copy()->setDay(15)->endOfDay();
        }
        
        // If we're after the 15th, next payment is end of month
        return $date->copy()->endOfMonth();
    }
} 
