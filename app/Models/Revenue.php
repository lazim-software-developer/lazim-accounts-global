<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Revenue extends Model
{
    protected $fillable = [
        'date',
        'amount',
        'account_id',
        'customer_id',
        'category_id',
        'recurring',
        'payment_method',
        'reference',
        'description',
        'created_by',
        'flat_id',
        'building_id',
        'receipt_period',
        'invoice_number',
        'transaction_date', // cheque draft date
        'transaction_method', // cheque transfer method
        'transaction_number' // cheque transfer number
    ];

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function customer()
    {
        return $this->hasOne('App\Models\Customer', 'id', 'customer_id');
    }

    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'account_id');
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_revenue')
            ->withPivot('adjusted_amount');
    }

    public function invoicePayments()
    {
        return $this->hasManyThrough(Invoice::class, 'App\Models\InvoiceRevenue', 'revenue_id', 'id', 'id', 'invoice_number');
    }

    public function bankAllocations()
    {
        return $this->hasMany('App\Models\RevenueBankAllocation', 'revenue_id');
    }

    public static function accounts($account)
    {
        $categoryArr  = explode(',', $account);
        $unitRate = 0;
        foreach ($categoryArr as $account) {
            if ($account == 0) {
                $unitRate = '';
            } else {
                $account        = BankAccount::find($account);
                // $unitRate   = ($account->bank_name ?? '');
                $unitRate    = ($account->bank_name . '  ' . $account->holder_name);
            }
        }

        return $unitRate;
    }

    public static function customers($customer)
    {
        $categoryArr  = explode(',', $customer);
        $unitRate = 0;
        foreach ($categoryArr as $customer) {
            if ($customer == 0) {
                $unitRate = '';
            } else {
                $customer       = Customer::find($customer);
                $unitRate       = ($customer->name);
            }
        }

        return $unitRate;
    }

    public static function categories($category)
    {
        $categoryArr  = explode(',', $category);
        $unitRate = 0;
        foreach ($categoryArr as $category) {
            if ($category == 0) {
                $unitRate = '';
            } else {
                $category        = ProductServiceCategory::find($category);
                $unitRate       = ($category->name);
            }
        }

        return $unitRate;
    }

    public function getCustomerTransactionLine()
    {
        return StakeholderTransactionLine::where('reference_id',  $this->id)->where('reference', "Revenue")->first();
    }

    public function updateCustomerBalance()
    {
        Utility::updateUserTransactionLine('customer', intval($this->customer_id), $this->amount, 'credit', "Revenue", $this->id, $this->date);
    }
    public function updateRevenueCustomerBalance($customer_id, $amount, $id, $date)
    {
        Utility::updateUserTransactionLine('customer', $customer_id, $amount, 'credit', "Revenue", $id, $date);
    }

    public function deleteCustomerTransactionLine()
    {
        if ($this->getCustomerTransactionLine()) {
            $transactionCreatedAt = $this->getCustomerTransactionLine()->created_at;
            $this->getCustomerTransactionLine()->delete();
            StakeholderTransactionLine::recalculateStakeholderBalances('customer_id', $this->customer_id, $transactionCreatedAt);
        }
    }
}
