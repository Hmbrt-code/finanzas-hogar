<?php

use App\Http\Controllers\AnnualController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallmentPlanController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('transactions', TransactionController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('/transactions/{transaction}/confirm', [TransactionController::class, 'confirm'])->name('transactions.confirm');
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('/annual', AnnualController::class)->name('annual.index');

    // Miembros
    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::post('/members/regenerate-code', [MemberController::class, 'regenerateCode'])->name('members.regenerateCode');
    Route::patch('/members/{member}/role', [MemberController::class, 'updateRole'])->name('members.updateRole');
    Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

    // Planes en cuotas
    Route::get('/plans', [InstallmentPlanController::class, 'index'])->name('plans.index');
    Route::post('/plans', [InstallmentPlanController::class, 'store'])->name('plans.store');
    Route::patch('/plans/{plan}', [InstallmentPlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}', [InstallmentPlanController::class, 'destroy'])->name('plans.destroy');
    Route::post('/plans/{plan}/pay', [InstallmentPlanController::class, 'pay'])->name('plans.pay');

    // Unirse a un hogar por código de invitación
    Route::get('/join/{code}', [MemberController::class, 'showJoin'])->name('members.showJoin');
    Route::post('/join/{code}', [MemberController::class, 'acceptJoin'])->name('members.acceptJoin');
});

require __DIR__.'/auth.php';
