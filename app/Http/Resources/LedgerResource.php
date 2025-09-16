<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LedgerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "name" => $this->name,
            "parent" => $this->subType->name ?? $this->parent,
            "openingBalance" => $this->opening_balance ?? '',
            "address" => $this->address ?? '',
            "emirate" => $this->emirate ?? 'Dubai',
            "country" => $this->country ?? 'United Arab Emirates',
            "pobox" => $this->pobox ?? '',
            "mobileNumber" => $this->mobile_number ?? '',
            "email" => $this->email ?? '',
            "registrationType" => $this->registration_type ?? '',
            "dateOfVatRegistration" => $this->date_of_vat_registration ?? '',
            "trn" => $this->trn ?? '',
            "ibanNumber" => $this->iban_number ?? '',
            "bankAccountNumber" => $this->bank_account_number ?? '',
            "bankName" => $this->bank_name ?? '',
            "bankHolderName" => $this->bank_holder_name ?? '',
            "accountType" => $this->account_type ?? '',
            "branch" => $this->branch ?? '',
            "bankCode" => $this->bank_code ?? '',
            "billByBill" => $this->bill_by_bill ?? '',
            "alias" => $this->alias ?? '',
        ];
    }
}
