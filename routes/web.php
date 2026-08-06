<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('workstations/replacements', 'pages::workstations.replacements')
        ->name('workstations.replacements');
});

require __DIR__.'/settings.php';
