<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'issue_date',
        'due_date',
        'send_date',
        'ref_number',
        'status',
        'category_id',
        'created_by',
        'flat_id',
        'building_id',
        'invoice_period',
        'invoice_pdf_link',
        'invoice_detail_link',
        'payment_url',
        'is_mollak',
    ];

    public static $statues = [
        'Draft',
        'Sent',
        'Unpaid',
        'Partialy Paid',
        'Paid',
    ];


    public function tax()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax_id');
    }

    public function items()
    {
        return $this->hasMany('App\Models\InvoiceProduct', 'invoice_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany('App\Models\InvoicePayment', 'invoice_id', 'id');
    }

    public function bankpayment()
    {
        return $this->hasMany('App\Models\BankTransfer', 'invoice_id', 'id')->where('type', '=', 'invoice')->where('status', '!=', 'Approved');
    }

    public function customer()
    {
        return $this->hasOne('App\Models\Customer', 'id', 'customer_id');
    }

    public function getSubTotal()
    {
        $subTotal = 0;
        foreach ($this->items as $product) {
            $subTotal += ($product->price * $product->quantity);
        }

        return $subTotal;
    }

    public function getTotalTax()
    {
        $totalTax = 0;
        foreach ($this->items as $product) {
            $taxes = Utility::totalTaxRate($product->tax);


            $totalTax += ($taxes / 100) * ($product->price * $product->quantity - $product->discount);
        }

        return $totalTax;
    }

    public function getTotalDiscount()
    {
        $totalDiscount = 0;
        foreach ($this->items as $product) {
            $totalDiscount += $product->discount;
        }

        return $totalDiscount;
    }

    public function getTotal()
    {
        return ($this->getSubTotal() - $this->getTotalDiscount()) + $this->getTotalTax();
    }

    public function getReceiptTotal()
    {
        return DB::table('invoice_revenue')->where('invoice_number', $this->id)->sum('adjusted_amount');
    }

    public static function invoiceNumberData($created_by = null)
    {
        $latest = Invoice::where('created_by', '=', $created_by ?? Auth::user()->creatorId())
            ->orderByDesc('id')
            ->first();

        if (!$latest) {
            return 1;
        } else {
            return $latest->id + 1;
        }
    }


    public function getDue()
    {
        $due = 0;
        foreach ($this->payments as $payment) {
            $due += $payment->amount;
        }
        $due += $this->getReceiptTotal();

        return ($this->getTotal() - $due) - $this->invoiceTotalCreditNote();
    }

    public static function change_status($invoice_id, $status)
    {

        $invoice         = Invoice::find($invoice_id);
        $invoice->status = $status;
        $invoice->update();
    }

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function creditNote()
    {

        return $this->hasMany('App\Models\CreditNote', 'invoice', 'id');
    }

    public function invoiceTotalCreditNote()
    {
        return $this->hasMany('App\Models\CreditNote', 'invoice', 'id')->sum('amount');
    }

    public function lastPayments()
    {
        return $this->hasOne('App\Models\InvoicePayment', 'id', 'invoice_id');
    }

    public function taxes()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax');
    }
    public static function customers($customer)
    {

        $categoryArr  = explode(',', $customer);
        $unitRate = 0;
        foreach ($categoryArr as $customer) {
            if ($customer == 0) {
                $unitRate = '';
            } else {
                $customer        = Customer::find($customer);
                $unitRate        = $customer->name;
            }
        }

        return $unitRate;
    }
    public static function Invoicecategory($category)
    {
        $categoryArr  = explode(',', $category);
        $categoryRate = 0;
        foreach ($categoryArr as $category) {
            $category    = ProductServiceCategory::find($category);
            $categoryRate        = $category->name;
        }

        return $categoryRate;
    }

    // public function setCreatedByAttribute($value)
    // {
    //     // For example, ensure that the value is always an integer
    //     $this->attributes['created_by'] = (int) $value;
    //     $this->attributes['building_id'] = \Auth::user()->building_id;
    // }

    public function getCustomerTransactionLine()
    {
        return StakeholderTransactionLine::where('reference_id',  $this->id)->where('reference', "Invoice")->first();
    }

    public function updateCustomerBalance()
    {
        Utility::updateUserTransactionLine('customer', $this->customer_id, $this->getTotal(), 'debit', "Invoice", $this->id, $this->issue_date);
    }

    public function deleteCustomerTransactionLine()
    {
        if ($this->getCustomerTransactionLine()) {
            $transactionCreatedAt = $this->getCustomerTransactionLine()->created_at;
            $this->getCustomerTransactionLine()->delete();
            StakeholderTransactionLine::recalculateStakeholderBalances('customer_id', $this->customer_id, $transactionCreatedAt);
        }
    }

    public function getTotalTaxWithName()
    {
        $incomeTaxesData = [];
        foreach ($this->items as $key => $item) {
            if (!empty($item->tax)) {
                $incomeTaxes = Utility::tax($item->tax);
                foreach ($incomeTaxes as $taxe) {
                    $taxDataPrice           = Utility::taxRate(!empty($taxe) ? ($taxe->rate) : 0, $item->price, $item->quantity);
                    if (!isset($incomeTaxesData[!empty($taxe) ? ($taxe->name) : ''])) {
                        $incomeTaxesData[!empty($taxe) ? ($taxe->name) : ''] = 0;
                    }
                    $incomeTaxesData[!empty($taxe) ? ($taxe->name) : ''] += $taxDataPrice;
                }
            }
        }

        return $incomeTaxesData;
    }
}
