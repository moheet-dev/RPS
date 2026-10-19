<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Loan as LoanModel;
use App\Services\CalculationService;

class Loan extends Controller
{
    public function create(Request $request, CalculationService $calculationService) {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'rate_of_interest' => 'required|numeric|gt:0',
            'no_of_installments' => 'required|numeric|gt:0'
        ]);
        $amount = $request -> input('amount');
        $rate_of_interest = $request -> input('rate_of_interest') / 1200;
        $no_of_installments = $request -> input('no_of_installments');
        $emi = $calculationService -> calculateEMI($amount, $rate_of_interest, $no_of_installments);
        $loan = LoanModel::create([
            'amount' => $amount,
            'rate_of_interest' => $request -> input('rate_of_interest'),
            'no_of_installments' => $request -> input('no_of_installments'),
            'emi' => $emi
        ]);
        return response() -> json([
            'message' => 'Loan created successfully',
            'emi' => $emi,
            'no_of_installments' => $no_of_installments,
            'principal_amount' => $amount,
            'annual_rate_of_interest' => $request -> input('rate_of_interest'),
            'loan_id' => $loan -> id
        ], 201);
    }

    public function read(Request $request, CalculationService $calculationService) {
        $loan_info = $request -> attributes -> get('loan_info');
        $amount = $loan_info -> amount;
        $rate_of_interest = $loan_info ->  rate_of_interest;
        $no_of_installments = $loan_info -> no_of_installments;
        $emi = $loan_info -> emi;
        return response() -> json([
            'message' => 'Loan read successful',
            'emi' => $emi,
            'no_of_installments' => $no_of_installments,
            'principal_amount' => $amount,
            'annual_rate_of_interest' => $loan_info ->  rate_of_interest
        ], 200);
    }
}
