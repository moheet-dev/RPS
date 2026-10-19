<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartPayment extends Model
{
    use HasFactory;
    protected $table = "part_payment";

    protected $fillable = [
        'amount',
        'recalculation_option',
        'loan_id'
    ];
}
