<?php

use App\Http\Controllers\AlmacenCompraController;
use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\AlmacenInsumoMovimientoController;
use App\Http\Controllers\CierreCajaController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\VentaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');

//login
Route::post('/login', [App\Http\Controllers\UserController::class, 'login']);
Route::middleware('auth:sanctum')->group(callback: function () {
    Route::post('/logout', [App\Http\Controllers\UserController::class, 'logout']);
    Route::get('/me', [App\Http\Controllers\UserController::class, 'me']);

    Route::get('/users', [App\Http\Controllers\UserController::class, 'index']);
    Route::post('/users', [App\Http\Controllers\UserController::class, 'store']);
    Route::put('/users/{user}', [App\Http\Controllers\UserController::class, 'update']);
    Route::delete('/users/{user}', [App\Http\Controllers\UserController::class, 'destroy']);
    Route::put('/updatePassword/{user}', [App\Http\Controllers\UserController::class, 'updatePassword']);
    Route::post('/{user}/avatar', [App\Http\Controllers\UserController::class, 'updateAvatar']);

    Route::get('/permissions', [App\Http\Controllers\UserController::class, 'permissions']);
    Route::get('/users/{user}/permissions', [App\Http\Controllers\UserController::class, 'getPermissions']);
    Route::put('/users/{user}/permissions', [App\Http\Controllers\UserController::class, 'syncPermissions']);

    Route::get('/tmdb/search', [\App\Http\Controllers\PeliculaController::class, 'tmdbSearch']);
    Route::get('/tmdb/movie/{tmdbId}', [\App\Http\Controllers\PeliculaController::class, 'tmdbDetail']);
    Route::get('/tmdb/movie/{tmdbId}/videos', [\App\Http\Controllers\PeliculaController::class, 'tmdbVideos']);

    Route::get('/peliculas', [\App\Http\Controllers\PeliculaController::class, 'index']);
    Route::get('/peliculas/{pelicula}', [\App\Http\Controllers\PeliculaController::class, 'show']);
    Route::post('/peliculas', [\App\Http\Controllers\PeliculaController::class, 'store']);
    Route::put('/peliculas/{pelicula}', [\App\Http\Controllers\PeliculaController::class, 'update']);
    Route::delete('/peliculas/{pelicula}', [\App\Http\Controllers\PeliculaController::class, 'destroy']);

    // Guardar desde TMDB (seleccionas una y la guardas)
    Route::post('/peliculas/from-tmdb', [\App\Http\Controllers\PeliculaController::class, 'storeFromTmdb']);

});
