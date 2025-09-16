<?php

namespace App\Core\Services;

use App\Core\Traits\AuthenticatedUserTrait;
use App\Core\Traits\ConnectionTrait;
use App\Core\Traits\PagingTrait;
use App\Core\Traits\UtilityTrait;
use App\Core\Traits\ValidationRequestTrait;
use App\Models\Bill;
use App\Models\Invoice;
use App\Models\Revenue;
use App\Models\Vender;
use Illuminate\Http\Request;

class ReportService
{
    use ConnectionTrait, PagingTrait, AuthenticatedUserTrait, ValidationRequestTrait;
    use UtilityTrait;

    public function getPagedInvoiceRecords(Request $request)
    {

        // Define the validation rules
        $rules = [
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'page' => 'required|integer',
            //'customer' => 'required|integer'
        ];

        // Validate the request with the dynamic messages
        $this->validateRequestWithDynamicMessages($request, $rules);

        // $query = Invoice::where('created_by',   $this->getOwnerId());
        $query = Invoice::with("customer");
        if (!$this->hasGlobalAccess())
            $query = $query->where('created_by', $this->getCreatorId());

        if (!empty($request->customer)) $query->where('customer_id', '=', $request->customer);
        if (!empty($request->from_date) && !empty($request->to_date))
            $query->whereBetween('issue_date', [$request->from_date, $request->to_date]);
        if (! empty($request->status)) {
            $status = $this->getIncrementedIndexFromLabel($request->status, Invoice::$statues);
            $query->where('status', '=',  $status);
        }
        $response = $this->paginateQuery($query, $request);
        return $this->paginateResponse($response);
    }

    public function getPagedAgingRecordsRecords(Request $request)
    {
        // Define the validation rules
        $rules = [
            // 'building' => 'required|integer',
            'page' => 'required|integer',
            'year' => 'required|integer'
        ];

        // Validate the request with the dynamic messages
        $this->validateRequestWithDynamicMessages($request, $rules);

        // $currentBuildId = \Auth::user()->currentBuilding();
        $currentBuildId = $request->building;
        $year = $request->year;
        $connection = $this->getLazimConnection();
        $query = $connection->table('aging_reports')
            ->join('flats', 'aging_reports.flat_id', '=', 'flats.id') // Assuming foreign key is flat_id
            ->join('apartment_owners', 'aging_reports.owner_id', '=', 'apartment_owners.id') // Join with the owners table
            ->join('buildings', 'aging_reports.building_id', '=', 'buildings.id'); // Join with the owners table

        $query->whereYear('aging_reports.updated_at', $year);

        if (!empty($currentBuildId)) {
            $query->where('aging_reports.building_id', $currentBuildId);
        }

        $query->select([
            'flats.property_number',
            'apartment_owners.name as owner_name',
            'aging_reports.outstanding_balance',
            'aging_reports.balance_1',
            'aging_reports.balance_2',
            'aging_reports.balance_3',
            'aging_reports.balance_4',
            'aging_reports.over_balance',
            'buildings.name'
        ]);

        $response = $this->paginateQuery($query, $request);
        return $this->paginateResponse($response);
    }

    public function getPagedReceiptsRecords(Request $request)
    {
        // Define the validation rules
        $rules = [
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'page' => 'required|integer',
            //'customer' => 'required|integer'
        ];

        // Validate the request with the dynamic messages
        $this->validateRequestWithDynamicMessages($request, $rules);

        // $query = Revenue::where('created_by', '=', \Auth::user()->creatorId());
        // Build the query with the base condition for customer_id
        $query = Revenue::with("customer", "category", "bankAccount");

        if (!empty($request->customer)) $query->where('customer_id', '=', $request->customer);
        if (!empty($request->from_date) && !empty($request->to_date))
            $query->whereBetween('date', [$request->from_date, $request->to_date]);


        if (!empty($request->account))
            $query->where('account_id', '=', $request->account);
        if (!empty($request->category))
            $query->where('category_id', '=', $request->category);
        if (!empty($request->transfer))
            $query->where('transfer_method', '=', $request->transfer);

        $response = $this->paginateQuery($query, $request);
        return $this->paginateResponse($response);
    }

    public function getPagedBillsRecords(Request $request)
    {
        // Define the validation rules
        $rules = [
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'page' => 'required|integer',
            // 'vender' => 'required|integer'
        ];

        // Validate the request with the dynamic messages
        $this->validateRequestWithDynamicMessages($request, $rules);


        // $query = Bill::where('created_by', '=', \Auth::user()->creatorId());
        $query = Bill::query();

        if (!empty($request->vender)) $query->where('vender_id', '=', $request->vender);
        if (!empty($request->from_date) && !empty($request->to_date))
            $query->whereBetween('bill_date', [$request->from_date, $request->to_date]);


        if (! empty($request->status)) {
            $status = $this->getIncrementedIndexFromLabel($request->status, Bill::$statues);
            $query->where('status', '=',  $status);
        }
        $response = $this->paginateQuery($query, $request);
        return $this->paginateResponse($response);
    }
}
