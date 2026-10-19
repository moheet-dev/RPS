<?php

namespace App\Services;

class CalculationService {
    public function calculateEMI($amount, $rate_of_interest, $no_of_installments) {
        $emi = round(($amount * $rate_of_interest * pow(1 + $rate_of_interest, $no_of_installments)) / (pow(1 + $rate_of_interest, $no_of_installments) - 1));
        return $emi;
    }

    public function calculateTenure($amount, $rate_of_interest, $emi) {
        $tenure = ceil((-log(1 - ($amount * $rate_of_interest) / $emi) / log(1 + $rate_of_interest)));
        return $tenure;
    }
}