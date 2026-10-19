<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Loan;

class LoanExists
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $loan_id = $request -> route('loan_id');
        $loan = Loan::findOrFail($loan_id);
        $request -> attributes -> set('loan_info', $loan);
        return $next($request);
    }
}
