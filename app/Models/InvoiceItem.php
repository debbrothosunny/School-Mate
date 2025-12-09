<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'fee_type_id',
        'description',
        'amount',
        'balance_due',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }
}