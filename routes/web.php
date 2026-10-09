<?php

use App\Http\Controllers\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HandoverController;
use App\Http\Controllers\Admin\KnowledgeItemController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\StylistController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\BeauticianChatController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SalonPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SalonPageController::class, 'index'])->name('home');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('services', ServiceController::class)->except('show');
        Route::resource('stylists', StylistController::class)->except('show');
        Route::resource('knowledge-items', KnowledgeItemController::class)->except('show');

        Route::get('appointments', [AdminAppointmentController::class, 'index'])->name('appointments.index');
        Route::patch('appointments/{appointment}/status', [AdminAppointmentController::class, 'updateStatus'])->name('appointments.status');

        Route::get('handovers', [HandoverController::class, 'index'])->name('handovers.index');
        Route::get('handovers/{handover}', [HandoverController::class, 'show'])->name('handovers.show');
        Route::post('handovers/{handover}/reply', [HandoverController::class, 'reply'])->name('handovers.reply');
        Route::post('handovers/{handover}/resolve', [HandoverController::class, 'resolve'])->name('handovers.resolve');
    });
});

Route::prefix('{salonSlug}')->middleware('salon')->group(function () {
    Route::get('/', [SalonPageController::class, 'show'])->name('salon.show');
    Route::get('services', [SalonPageController::class, 'services'])->name('salon.services');
    Route::get('services/{serviceSlug}', [SalonPageController::class, 'service'])->name('salon.service');
    Route::get('stylists', [SalonPageController::class, 'stylists'])->name('salon.stylists');
    Route::get('stylists/{stylistSlug}', [SalonPageController::class, 'stylist'])->name('salon.stylist');
    Route::get('facilities', [SalonPageController::class, 'facilities'])->name('salon.facilities');
    Route::get('facilities/{facilityId}', [SalonPageController::class, 'facility'])->whereNumber('facilityId')->name('salon.facility');
    Route::get('info', [SalonPageController::class, 'info'])->name('salon.info');
    Route::get('staff', [SalonPageController::class, 'staff'])->name('salon.staff');
    Route::get('appointment', [SalonPageController::class, 'appointmentScene'])->name('salon.appointment');

    Route::prefix('appointment')->name('appointment.')->middleware('throttle:20,1')->group(function () {
        Route::post('slots', [AppointmentController::class, 'slots'])->name('slots');
        Route::post('quote', [AppointmentController::class, 'quote'])->name('quote');
        Route::post('/', [AppointmentController::class, 'store'])->name('store');
    });

    Route::prefix('beautician')->name('beautician.')->group(function () {
        Route::post('start', [BeauticianChatController::class, 'start'])->middleware('throttle:beautician-start')->name('start');
        Route::post('message', [BeauticianChatController::class, 'message'])->middleware('throttle:beautician-message')->name('message');
        Route::get('history', [BeauticianChatController::class, 'history'])->middleware('throttle:beautician-history')->name('history');
    });
});
