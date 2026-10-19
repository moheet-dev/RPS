<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;
    protected $table = "loan";

    protected $fillable = [
        'amount',
        'rate_of_interest',
        'no_of_installments',
        'emi'
    ];
}
