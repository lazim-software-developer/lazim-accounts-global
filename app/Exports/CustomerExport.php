<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $data = [];

        if (in_array(\Auth::user()->type, ['company', 'building'])) {
            $data = Customer::select('customer_id', 'name', 'email', 'tax_number', 'contact', 'billing_name', 'billing_country', 'billing_state', 'billing_city', 'billing_phone', 'billing_zip', 'billing_address', 'shipping_name', 'shipping_country', 'shipping_state', 'shipping_city', 'shipping_phone', 'shipping_zip', 'shipping_address', 'balance as total_balance')->where('created_by', \Auth::user()->creatorId())->get();
        } else {
            $data = Customer::select('customer_id', 'name', 'email', 'tax_number', 'contact', 'billing_name', 'billing_country', 'billing_state', 'billing_city', 'billing_phone', 'billing_zip', 'billing_address', 'shipping_name', 'shipping_country', 'shipping_state', 'shipping_city', 'shipping_phone', 'shipping_zip', 'shipping_address', 'balance as total_balance')->get();
        }

        if (!empty($data)) {
            foreach ($data as $k => $customer) {
                unset($customer->id, $customer->avatar, $customer->is_active, $customer->password, $customer->created_at, $customer->updated_at, $customer->is_enable_login, $customer->lang, $customer->created_by, $customer->email_verified_at, $customer->remember_token);
                $data[$k]['customer_id'] = \Auth::user()->customerNumberFormat($customer->customer_id);
                // $data[$k]["balance"]     = number_format($customer->balance, 2, '.', '');
            }
        }
        return $data;
    }

    public function headings(): array
    {
        return [
            'Customer ID',
            'Name',
            'Email',
            'Tax Number',
            'Contact',
            'Billing Name',
            'Billing Country',
            'Billing State',
            'Billing City',
            'Billing Phone',
            'Billing Zip',
            'Billing Address',
            'Shipping Name',
            'Shipping Country',
            'Shipping State',
            'Shipping City',
            'Shipping Phone',
            'Shipping Zip',
            'Shipping Address',
            'Balance',
        ];
    }
}
