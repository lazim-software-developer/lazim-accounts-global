<?php

use App\Http\Controllers\Api\Common\AuthenticationController;
use App\Http\Controllers\Api\Common\ExampleController;
use App\Http\Controllers\Api\Common\InvoiceController;
use App\Http\Controllers\Api\Common\ReportController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Api\Tally\TallyIntigrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('bill/create', [BillController::class, 'createBillFromLazim']);

Route::post('register', [RegisteredUserController::class, 'store']);
Route::post('customer', [CustomerController::class, 'store']);

Route::middleware(['authenticate.tally'])->group(function () {
    Route::get('/V1/getSalesVouchers', [TallyIntigrationController::class, 'getSalesVouchers']);
    Route::get('/V1/getGroups', [TallyIntigrationController::class, 'getGroups']);
    Route::get('/V1/getCostCategory', [TallyIntigrationController::class, 'getCostCategory']);
    Route::get('/V1/getCostCentre', [TallyIntigrationController::class, 'getCostCentre']);
    Route::get('/V1/getLedgers', [TallyIntigrationController::class, 'getLedgers']);
    Route::post('/V1/acknowledgements', [TallyIntigrationController::class, 'acknowledgements']);
    Route::get('/V1/getPurchaseVoucher', [TallyIntigrationController::class, 'getPurchaseVouchers']);
    Route::get('/V1/getPaymentVoucher', [TallyIntigrationController::class, 'getPaymentVouchers']);
    Route::get('/V1/getReceiptVoucher', [TallyIntigrationController::class, 'getReceiptVouchers']);
    Route::get('/V1/getContraVouchers', [TallyIntigrationController::class, 'getContraVouchers']);
    Route::get('/V1/getCreditNoteVouchers', [TallyIntigrationController::class, 'getCreditNoteVouchers']);
    Route::get('/V1/getDebitNoteVouchers', [TallyIntigrationController::class, 'getDebitNoteVouchers']);
    Route::get('/V1/getJournalVouchers', [TallyIntigrationController::class, 'getJournalVouchers']);
    Route::get('/V1/getBudget', [TallyIntigrationController::class, 'getBudget']);
});


// testing

Route::prefix('example')->group(function () {
    Route::get('/data', [ExampleController::class, 'getData']);
    Route::get('/users', [ExampleController::class, 'getUsers']);
    Route::post('/store', [ExampleController::class, 'store']);
    Route::get('/validation-error', [ExampleController::class, 'validationErrorExample']);
});


Route::prefix('auth')->group(function () {
    // Login route
    Route::post('login', [AuthenticationController::class, 'login']);

    // Refresh access token route
    Route::post('refresh', [AuthenticationController::class, 'refresh']);

    Route::middleware(['auth.sanctum'])->group(function () {

        // read authenticated user
        Route::get('user', [AuthenticationController::class, 'authenticatedUser']);

        // Logout route
        Route::post('logout', [AuthenticationController::class, 'logout']);

        // Restore a soft-deleted token route
        Route::post('restore-token', [AuthenticationController::class, 'restoreToken']);

        // Permanently delete a soft-deleted token route
        Route::post('delete-token', [AuthenticationController::class, 'deleteToken']);
    });

    // Route::middleware(middleware: 'auth.sanctum-custom')->post('delete-token', [AuthenticationController::class, 'deleteToken']);
});

Route::middleware(['auth.sanctum'])->group(function () {
    // Route::prefix('invoice')->group(function () {
    //     // paged invoice
    //     Route::post('list', [InvoiceController::class, 'getPagedInvoices']);
    // });

    Route::prefix('report')->group(function () {
        // paged invoice
        Route::post('invoice', [ReportController::class, 'getPagedInvoiceRecords']);
        Route::post('receipt', [ReportController::class, 'getPagedReceiptsRecords']);
        Route::post('aging', [ReportController::class, 'getPagedAgingRecordsRecords']);
        Route::post('bill', [ReportController::class, 'getPagedBillsRecords']);
    });
});
