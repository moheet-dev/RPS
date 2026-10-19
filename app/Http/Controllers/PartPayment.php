<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CalculationService;
use App\Models\PartPayment as PartPaymentModel;

class PartPayment extends Controller
{
    public function create(Request $request, CalculationService $calculationService) {
        $request -> validate([
            'amount' => 'required|numeric|gt:0',
            'recalculation_option' => 'required|string|in:reduce_emi,reduce_tenure'
        ]);
        
        $amount = $request -> input('amount');
        $recalculation_option = $request -> input('recalculation_option');
        $loan_info = $request -> attributes -> get('loan_info');
        $rate_of_interest = $loan_info -> rate_of_interest / 1200;
        $updated_principal = $loan_info -> amount - $amount;
        $current_emi = $loan_info -> emi;
        $loan_info -> amount = $updated_principal;
        switch($recalculation_option) {
            case 'reduce_tenure':
                $new_tenure = $calculationService -> calculateTenure($updated_principal, $rate_of_interest, $current_emi);
                $loan_info -> no_of_installments = $new_tenure;
                $loan_info -> save();
                PartPaymentModel::create([
                    'amount' => $amount,
                    'recalculation_option' => $recalculation_option,
                    'loan_id' => $loan_info -> id
                ]);
                return response() -> json([
                    'message' => 'Part Payment created successfully',
                    'part_payment_amount' => $amount,
                    'recalculation_option' => $recalculation_option,
                    'emi' => $current_emi,
                    'no_of_installments' => $new_tenure,
                    'principal_amount' => $updated_principal,
                    'annual_rate_of_interest' => $loan_info -> rate_of_interest
                ], 201);
            case 'reduce_emi':
                $updated_emi = $calculationService -> calculateEMI($updated_principal, $rate_of_interest , $loan_info -> no_of_installments);
                $loan_info -> emi = $updated_emi;
                $loan_info -> save();
                PartPaymentModel::create([
                    'amount' => $amount,
                    'recalculation_option' => $recalculation_option,
                    'loan_id' => $loan_info -> id
                ]);
                return response() -> json([
                    'message' => 'Part Payment created successfully',
                    'part_payment_amount' => $amount,
                    'recalculation_option' => $recalculation_option,
                    'emi' => $updated_emi,
                    'no_of_installments' => $loan_info -> no_of_installments,
                    'principal_amount' => $updated_principal,
                    'annual_rate_of_interest' => $loan_info -> rate_of_interest
                ], 201);
        }
    }

    public function read(Request $request) {
        $loan_id = $request -> route('loan_id');
        $part_payments = PartPaymentModel::where('loan_id', '=', $loan_id)->get();
        return response() -> json([
            'message' => 'Part Payment read successful',
            'part_payments' => $part_payments
        ], 200);
    }
}
