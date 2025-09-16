<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vender_id',
        'currency',
        'bill_date',
        'due_date',
        'bill_id',
        'order_number',
        'category_id',
        'created_by',
        'deleted_at',
        'ref_number',
        'building_id',
        'send_date',
        'status',
        'is_mollak',
        'wda_document',
        'wda_number',
        'total_amount',
        'total_due',
    ];

    public static $statues = [
        'Draft',
        'Sent',
        'Unpaid',
        'Partialy Paid',
        'Paid',
    ];

    public function vender()
    {
        return $this->hasOne('App\Models\Vender', 'id', 'vender_id');
    }

    public function tax()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax_id');
    }

    public function items()
    {
        return $this->hasMany('App\Models\BillProduct', 'bill_id', 'id')->orderBy('id', 'asc');
    }

    public function payments()
    {
        return $this->hasMany('App\Models\BillPayment', 'bill_id', 'id');
    }

    public function accounts()
    {
        return $this->hasMany('App\Models\BillAccount', 'ref_id', 'id');
    }

    public function getSubTotal()
    {
        $subTotal = 0;

        foreach ($this->items as $product) {
            $subTotal += ($product->price * $product->quantity);
        }

        $accountTotal = 0;
        foreach ($this->accounts as $account) {
            $accountTotal += $account->price;
        }

        return $subTotal + $accountTotal;
    }

    // public function getTotalTax()
    // {
    //     $totalTax = 0;
    //     foreach ($this->items as $product) {
    //         $taxes = Utility::totalTaxRate($product->tax);

    //         $totalTax += ($taxes / 100) * ($product->price * $product->quantity);
    //     }

    //     return $totalTax;
    // }

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

    // public function getTotal()
    // {
    //     return ($this->getSubTotal() + $this->getTotalTax()) - $this->getTotalDiscount();
    // }

    public function getTotal()
    {

        return ($this->getSubTotal() - $this->getTotalDiscount()) + $this->getTotalTax();
        //        return ($this->getSubTotal() + $this->getTotalTax()) - $this->getTotalDiscount();
    }

    public function getDue()
    {
        $due = 0;
        foreach ($this->payments as $payment) {
            $due += $payment->amount;
        }

        return ($this->getTotal() - $due) - ($this->billTotalDebitNote());
    }

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function debitNote()
    {
        return $this->hasMany('App\Models\DebitNote', 'bill', 'id');
    }

    public function billTotalDebitNote()
    {
        return $this->hasMany('App\Models\DebitNote', 'bill', 'id')->sum('amount');
    }

    public function lastPayments()
    {
        return $this->hasOne('App\Models\BillPayment', 'id', 'bill_id');
    }

    public function taxes()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax');
    }

    public static function vendor($venders)
    {

        $categoryArr = explode(',', $venders);
        $unitRate = 0;
        foreach ($categoryArr as $venders) {
            if ($venders == 0) {
                $unitRate = '';
            } else {
                $venders = Vender::find($venders);
                $unitRate = ($venders) ? $venders->name : ' ';
            }
        }

        return $unitRate;
    }

    public static function ProposalCategory($category)
    {
        $categoryArr = explode(',', $category);
        $categoryRate = 0;
        foreach ($categoryArr as $category) {
            $category = ProductServiceCategory::find($category);
            $categoryRate = $category->name;
        }

        return $categoryRate;
    }

    public function getAccountTotal()
    {
        $accountTotal = 0;
        foreach ($this->accounts as $account) {
            $accountTotal += $account->price;
        }

        return $accountTotal;
    }

    public function getVendorTransactionLine()
    {
        return StakeholderTransactionLine::where('reference_id', $this->id)->where('reference', 'Bill')->first();
    }

    public function updateVendorBalance()
    {
        Utility::updateUserTransactionLine('vendor', $this->vender_id, $this->getTotal(), 'credit', 'Bill', $this->id, $this->bill_date);
    }
    public function updateBillVendorBalance()
    {
        Utility::updateUserTransactionLine('vendor', $this->vender_id, $this->total_amount, 'credit', 'Bill', $this->id, $this->bill_date);
    }

    public function deleteVendorTransactionLine()
    {
        if ($this->getVendorTransactionLine()) {
            $transactionCreatedAt = $this->getVendorTransactionLine()->created_at;
            $this->getVendorTransactionLine()->delete();
            StakeholderTransactionLine::recalculateStakeholderBalances('vender_id', $this->vender_id, $transactionCreatedAt);
        }
    }

    public function getTotalTaxWithName()
    {
        $expenseTaxesData = [];
        foreach ($this->items as $key => $item) {
            if (! empty($item->tax)) {
                $expenseTaxes = Utility::tax($item->tax);
                foreach ($expenseTaxes as $taxe) {
                    $taxDataPrice = Utility::taxRate(! empty($taxe) ? ($taxe->rate) : 0, $item->price, $item->quantity);
                    if (! isset($expenseTaxesData[! empty($taxe) ? ($taxe->name) : ''])) {
                        $expenseTaxesData[! empty($taxe) ? ($taxe->name) : ''] = 0;
                    }
                    $expenseTaxesData[! empty($taxe) ? ($taxe->name) : ''] += $taxDataPrice;
                }
            }
        }

        return $expenseTaxesData;
    }
}
