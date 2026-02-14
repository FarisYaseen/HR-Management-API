<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\PositionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::get('/employees/{employee}/hierarchy/names', [EmployeeController::class, 'hierarchyNames']);
        Route::get('/employees/{employee}/hierarchy/names-salaries', [EmployeeController::class, 'hierarchyNamesAndSalaries']);
        Route::get('/employees-search', [EmployeeController::class, 'search']);
        Route::get('/employees-no-recent-salary-change', [EmployeeController::class, 'withoutRecentSalaryChange']);
        Route::get('/employees-export/csv', [EmployeeController::class, 'exportCsv']);
        Route::post('/employees-import/csv', [EmployeeController::class, 'importCsv']);
        Route::apiResource('employees', EmployeeController::class);
        Route::apiResource('positions', PositionController::class);
    });
});
