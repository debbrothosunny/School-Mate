<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // Make sure Carbon is available for date calculations

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'invoice_number',
        'billing_period',
        'due_date',
        'total_amount_due',
        'amount_paid',
        'balance_due',
        'status',
        'issued_at',
        'paid_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
    ];
    
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }


  
}