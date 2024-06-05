<?php
use Illuminate\Support\Facades\Route;
use App\Livewire\Home;



Route::get('/', function ()  {
    return view("welcome");
})->name('home');



require __DIR__ . '/auth.php';
