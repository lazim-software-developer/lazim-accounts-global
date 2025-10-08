<?php

namespace App\Exports;

use App\Models\Vender;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VenderExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $data = [];

        if (in_array(\Auth::user()->type, ['company', 'building'])) {
            $data = Vender::select('vender_id','name','email','tax_number','contact','billing_name','billing_country','billing_state','billing_city','billing_phone','billing_zip','billing_address','shipping_name','shipping_country','shipping_state','shipping_city','shipping_phone','shipping_zip','shipping_address','balance')->where('created_by', \Auth::user()->creatorId())->get();
        } else {
            $data = Vender::select('vender_id','name','email','tax_number','contact','billing_name','billing_country','billing_state','billing_city','billing_phone','billing_zip','billing_address','shipping_name','shipping_country','shipping_state','shipping_city','shipping_phone','shipping_zip','shipping_address','balance')->get();
        }

        if (!empty($data)) {
            foreach ($data as $k => $vendor) {
                $data[$k]["vender_id"]        = \Auth::user()->venderNumberFormat($vendor->vender_id);
                $data[$k]["balance"]          = \Auth::user()->priceFormat($vendor->balance);
            }
        }
        return $data;
    }

    public function headings(): array
    {
        return [
            "Vendor ID",
            "Name",
            "Email",
            "Tax Number",
            "Contact",
            "Billing Name",
            "Billing Country",
            "Billing State",
            "Billing City",
            "Billing Phone",
            "Billing Zip",
            "Billing Address",
            "Shipping Name",
            "Shipping Country",
            "Shipping State",
            "Shipping City",
            "Shipping Phone",
            "Shipping Zip",
            "Shipping Address",
            "Balance",
        ];
    }
}
