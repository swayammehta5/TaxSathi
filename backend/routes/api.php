<?php

/*
|--------------------------------------------------------------------------
| REST API (versionless; consumed by Next.js)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ContactController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\FirmController;
use App\Http\Controllers\API\InvoiceController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:auth-login');
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:auth-login');

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:contact-form');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/user', [UserController::class, 'current']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/team', [UserController::class, 'index']);
    Route::get('/firms', [FirmController::class, 'index']);
    Route::post('/firms', [FirmController::class, 'store']);

    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    // Invoices: staff may view; admin manages
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download']);
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf']);

    // Attendance: staff + admin
    Route::post('/attendance', [AttendanceController::class, 'bulkStore']);
    Route::get('/attendance', [AttendanceController::class, 'byDate']);
    Route::get('/attendance/history/{employee}', [AttendanceController::class, 'history']);

    Route::middleware('role:admin')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::post('/employees', [EmployeeController::class, 'store']);
        Route::get('/employees/{employee}', [EmployeeController::class, 'show']);
        Route::put('/employees/{employee}', [EmployeeController::class, 'update']);
        Route::patch('/employees/{employee}', [EmployeeController::class, 'update']);
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy']);

        Route::post('/invoices', [InvoiceController::class, 'store']);
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update']);
        Route::patch('/invoices/{invoice}', [InvoiceController::class, 'update']);
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);
        Route::post('/invoices/{invoice}/send-email', [InvoiceController::class, 'sendEmail']);

        Route::apiResource('attendances', AttendanceController::class);

        Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);
        Route::get('/reports/attendance', [ReportController::class, 'attendance']);
        Route::get('/reports/attendance/yearly', [ReportController::class, 'attendanceYearly']);
        Route::get('/reports/invoices', [ReportController::class, 'invoices']);
    });
});
