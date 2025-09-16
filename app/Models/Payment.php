<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'date',
        'amount',
        'account_id',
        'vender_id',
        'description',
        'category_id',
        'payment_method',
        'reference',
        'created_by',
    ];

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function vender()
    {
        return $this->hasOne('App\Models\Vender', 'id', 'vender_id');
    }


    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'account_id');
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
                $unitRate    = ($account->bank_name.'  '.$account->holder_name);
                // dd($unitRate);

            }
        }

        return $unitRate;
    }

    public static function vendors($vendor)
    {
        $categoryArr  = explode(',', $vendor);
        $unitRate = 0;
        foreach ($categoryArr as $vendor) {
            if ($vendor == 0) {
                $unitRate = '';
            } else {
                $vendor       = Vender::find($vendor);
                $unitRate       = ($vendor->name);
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
                $category       = ProductServiceCategory::find($category);
                $unitRate       = ($category->name);
            }
        }

        return $unitRate;
    }

    public function getVendorTransactionLine(){
        return StakeholderTransactionLine::where('reference_id',  $this->id)->where('reference', 'Payment')->first();
    }

    public function updateVendorBalance(){
        Utility::updateUserTransactionLine('vendor', $this->vender_id, $this->amount, 'debit', 'Payment', $this->id, $this->date);
    }

    public function deleteVendorTransactionLine(){
        if($this->getVendorTransactionLine()){
            $transactionCreatedAt = $this->getVendorTransactionLine()->created_at;
            $this->getVendorTransactionLine()->delete();
            StakeholderTransactionLine::recalculateStakeholderBalances('vender_id', $this->vender_id, $transactionCreatedAt);
        }
    }
}
