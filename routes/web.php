<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProfileController;
use App\Services\Supabase\SupabaseService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/dashboard');
})->middleware('supabase.auth');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['supabase.auth', 'supabase.permissions'])->name('home');

Route::middleware(['supabase.auth', 'supabase.permissions'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Specify the storage folder
    $directory = 'json'; // Example: 'app/json' if your files are stored in storage/app/json

    // Read all json files from the directory
    $files = Storage::files($directory);
    foreach ($files as $file) {
        Route::resource(str_replace('.json', '', explode('/', $file)[1]), \App\Http\Controllers\GeneralController::class)->middleware('json.props');
    }
    
    // API route for loading subcategories
    Route::get('/api/subcategories/{table}', [\App\Http\Controllers\GeneralController::class, 'getSubcategories'])->name('api.subcategories');
    
    // API routes for gallery operations
    Route::post('/api/gallery/upload', [\App\Http\Controllers\GeneralController::class, 'uploadGalleryImage'])->name('api.gallery.upload');
    Route::delete('/api/gallery/delete', [\App\Http\Controllers\GeneralController::class, 'deleteGalleryImage'])->name('api.gallery.delete');
    Route::get('/api/gallery/{galleryId}/images', [\App\Http\Controllers\GeneralController::class, 'listGalleryImages'])->name('api.gallery.list');
    
    // Calendar routes
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
});


require __DIR__.'/auth.php';

Route::get('/forbidden', function () {
    return response()->view('errors.403', [], 403);
})->name('error.403');

Route::post('/ai/generate-image', [\App\Http\Controllers\GeneralController::class, 'generateAiImage'])->name('ai.generate-image');
Route::post('/ai/generate-description', [\App\Http\Controllers\GeneralController::class, 'generateAiDescription'])->name('ai.generate-description');
