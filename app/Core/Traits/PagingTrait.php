<?php

namespace App\Core\Traits;

use App\Core\Services\ResponseService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait PagingTrait
{


    /**
     * Paginate a given query with request parameters.
     *
     * @param $query
     * @param Request $request
     * @param int $defaultPerPage
     * @return LengthAwarePaginator
     */
    // public function paginateQuery($query, Request $request, $defaultPerPage = 10): LengthAwarePaginator
    // {
    //     $perPage = $request->get('per_page', $defaultPerPage); // Default to 10
    //     $page = $request->get('page', 1); // Default to page 1

    //     return $query->paginate($perPage, ['*'], 'page', $page);
    // }
    // public function paginateQuery($query, Request $request, $defaultPerPage = 10): LengthAwarePaginator
    // {
    //     $perPage = $request->get('per_page', $defaultPerPage); // Default to 10
    //     $page = $request->get('page', 1); // Default to page 1

    //     // Get the order by field and direction from the request, defaulting to 'created_at' and 'asc' if not provided
    //     $orderBy = $request->get('order_by', 'id'); // Default to ordering by 'created_at'
    //     $direction = $request->get('direction', 'desc'); // Default to 'asc' direction

    //     // Apply the ordering dynamically if the field is valid
    //     if (in_array(strtolower($direction), ['asc', 'desc'])) {
    //         // $query->orderBy($orderBy, $direction);
    //         $query->orderBy($query->getModel()->getTable() . '.' . $orderBy, $direction);
    //     }

    //     return $query->paginate($perPage, ['*'], 'page', $page);
    // }

    public function paginateQuery($query, Request $request, $defaultPerPage = 10): LengthAwarePaginator
    {
        $perPage = $request->get('per_page', $defaultPerPage); // Default to 10
        $page = $request->get('page', 1); // Default to page 1

        // Get the order by field and direction from the request, defaulting to 'id' and 'desc' if not provided
        $orderBy = $request->get('order_by', 'id'); // Default to ordering by 'id'
        $direction = $request->get('direction', 'desc'); // Default to 'desc' direction

        // // Define valid columns for ordering to prevent SQL injection
        // $validOrderByColumns = ['id', 'created_at', 'updated_at']; // Modify as per your columns

        // // If the provided 'order_by' is not in the valid columns, fall back to 'id'
        // if (!in_array($orderBy, $validOrderByColumns)) {
        //     $orderBy = 'id';
        // }

        // Determine the table name dynamically
        $tableName = $this->getTableName($query);

        // Apply the ordering dynamically if the field is valid
        if (in_array(strtolower($direction), ['asc', 'desc'])) {
            // Ensure we refer to the correct column by using the table alias (e.g., 'flats.id')
            $query->orderBy($tableName . '.' . $orderBy, $direction);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get the table name dynamically based on the query type (Eloquent or Query Builder).
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @return string
     */
    private function getTableName($query)
    {
        // Check if the query is an instance of the Eloquent Builder
        if ($query instanceof \Illuminate\Database\Eloquent\Builder) {
            return $query->getModel()->getTable(); // Eloquent model's table name
        }

        // If it's a Query Builder, check for the from clause to get the table name
        if ($query instanceof \Illuminate\Database\Query\Builder) {
            return $query->from; // Returns the table name set in the query
        }

        // Default fallback (if you want to ensure it always returns a value)
        return null; // Replace with your default table name
    }



    /**
     * Paginate and format response using ApiResponseService
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function paginateResponse(LengthAwarePaginator $paginator, $message = 'Data retrieved successfully')
    {
        // Check if there are no items in the paginator
        if ($paginator->isEmpty()) $message = 'No data found';

        return ResponseService::success([
            'items'         => $paginator->items(),
            'current_page'  => $paginator->currentPage(),
            'per_page'      => $paginator->perPage(),
            'total'         => $paginator->total(),
            'total_pages'   => $paginator->lastPage(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
        ], $message);
    }
}
