<?php

namespace App\Exports;

use App\Models\ProductService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductServiceExport implements FromCollection, WithHeadings
{

    public function collection()
    {
        $data = [];

        if(in_array(\Auth::user()->type,['company','building']))
        {
            $data = ProductService::where('created_by' , \Auth::user()->creatorId())->get();
        }
        else{
            $data = ProductService::get();
        }

        if (!empty($data)) {
            foreach ($data as $k => $ProductService) {
                $taxe  = ProductService::Taxe($ProductService->tax_id);
                $unit  = ProductService::productserviceunit($ProductService->unit_id);
                $category  = ProductService::productcategory($ProductService->category_id);


                unset($ProductService->created_by,$ProductService->sku, $ProductService->updated_at, $ProductService->created_at);
                $data[$k]["tax_id"]       = $taxe;
                $data[$k]["unit_id"]       = $unit;
                $data[$k]["category_id"]   = $category;

            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            "ID",
            "Name",
            "Sale Price",
            "Purchase Price",
            "Quantity",
            "Tax",
            "Category",
            "Unit",
            "Type",
            "Description",
        ];
    }
}
