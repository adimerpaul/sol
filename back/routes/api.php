<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaisController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\ProvinciaController;
use App\Http\Controllers\MunicipioController;
use App\Http\Controllers\LocalidadController;
use App\Http\Controllers\RecintoController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\PartidoController;
use App\Http\Controllers\AdminUserRecintoController;
use App\Http\Controllers\ResultadoMesaController;
use App\Http\Controllers\RecintoMapaController;
use App\Http\Controllers\EleccionesDashboardController;
use App\Http\Controllers\GraficosController;
use App\Http\Controllers\AdminUserJerarquiaController;
use App\Http\Controllers\AdminRecintoJefeMapaController;
use App\Http\Controllers\MesaAiControlController;
use App\Http\Controllers\SuperAdminMesasController;
use App\Http\Controllers\ReportesController;
//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');

Route::post('/login', [App\Http\Controllers\UserController::class, 'login']);
Route::middleware('auth:sanctum')->group(callback: function () {
    Route::post('/logout', [App\Http\Controllers\UserController::class, 'logout']);
    Route::get('/me', [App\Http\Controllers\UserController::class, 'me']);
    Route::put('/me/profile', [App\Http\Controllers\UserController::class, 'updateMyProfile']);

    Route::post('/users/{user}/files', [App\Http\Controllers\UserController::class, 'updateFiles']);


    Route::get('/users', [App\Http\Controllers\UserController::class, 'index']);
    Route::get('/users/print/{type}', [App\Http\Controllers\UserController::class, 'printByType']);
    Route::post('/users', [App\Http\Controllers\UserController::class, 'store']);
    Route::put('/users/{user}', [App\Http\Controllers\UserController::class, 'update']);
    Route::patch('/users/{user}/username', [App\Http\Controllers\UserController::class, 'updateUsername']);
    Route::delete('/users/{user}', [App\Http\Controllers\UserController::class, 'destroy']);
    Route::put('/updatePassword/{user}', [App\Http\Controllers\UserController::class, 'updatePassword']);
    Route::post('/{user}/avatar', [App\Http\Controllers\UserController::class, 'updateAvatar']);

    Route::get('/permissions', [App\Http\Controllers\UserController::class, 'permissions']);
    Route::get('/users/{user}/permissions', [App\Http\Controllers\UserController::class, 'getPermissions']);
    Route::put('/users/{user}/permissions', [App\Http\Controllers\UserController::class, 'syncPermissions']);

    Route::apiResource('paises', PaisController::class);
    Route::apiResource('departamentos', DepartamentoController::class);
    Route::apiResource('provincias', ProvinciaController::class);
    Route::get('municipios/{municipio}/partidos', [MunicipioController::class, 'partidos']);
    Route::put('municipios/{municipio}/partidos', [MunicipioController::class, 'syncPartidos']);
    Route::apiResource('municipios', MunicipioController::class);
    Route::apiResource('localidades', LocalidadController::class);
    Route::apiResource('recintos', RecintoController::class);
    Route::apiResource('mesas', MesaController::class);

// helpers para combos (cascada)
    Route::get('geo/options', [PaisController::class, 'options']);

    Route::apiResource('partidos', PartidoController::class);

    Route::get('admin/users-recintos', [AdminUserRecintoController::class, 'users']);
    Route::get('admin/users-recintos/bootstrap', [AdminUserRecintoController::class, 'bootstrap']);
    Route::get('admin/recintos-oruro', [AdminUserRecintoController::class, 'recintosOruro']);
    Route::get('admin/recintos-oruro-city', [RecintoController::class, 'oruroCity']);
    Route::get('admin/recintos-no-asignados', [AdminUserRecintoController::class, 'recintosNoAsignados']);
    Route::put('admin/users/{user}/recintos', [AdminUserRecintoController::class, 'sync']);

    Route::get('resultados/mesas-asignadas', [ResultadoMesaController::class, 'mesasAsignadas']);
    Route::get('resultados/mesa/{mesa}', [ResultadoMesaController::class, 'showByMesa']);
    Route::post('resultados', [ResultadoMesaController::class, 'store']);

    Route::get('mapas/catalogo', [RecintoMapaController::class, 'catalogo']);
    Route::get('mapas/provincias/{provinciaId}/municipios', [RecintoMapaController::class, 'municipios']);
    Route::get('mapas/municipios/{municipioId}/localidades', [RecintoMapaController::class, 'localidades']);

    Route::get('mapas/recintos', [RecintoMapaController::class, 'recintos']);
    Route::get('mapas/recintos/{recinto}', [RecintoMapaController::class, 'show']);
    Route::put('mapas/recintos/{recinto}', [RecintoMapaController::class, 'update']);

    Route::middleware('auth:sanctum')->get('/dashboard/elecciones/resumen', [EleccionesDashboardController::class, 'resumen']);
    Route::middleware('auth:sanctum')->get('/dashboard/graficos', [GraficosController::class, 'index']);
    Route::middleware('auth:sanctum')->get('/dashboard/mapa', [GraficosController::class, 'mapa']);

    Route::get('admin/jerarquia/supervisores', [AdminUserJerarquiaController::class, 'supervisores']);
    Route::get('admin/jerarquia/jefes', [AdminUserJerarquiaController::class, 'jefes']);
    Route::get('admin/jerarquia/delegados', [AdminUserJerarquiaController::class, 'delegados']);

    Route::get('admin/jerarquia/supervisores/{supervisor}/jefes', [AdminUserJerarquiaController::class, 'supervisorJefes']);
    Route::put('admin/jerarquia/supervisores/{supervisor}/jefes', [AdminUserJerarquiaController::class, 'syncSupervisorJefes']);

    Route::get('admin/jerarquia/jefes/{jefe}/delegados', [AdminUserJerarquiaController::class, 'jefeDelegados']);
    Route::put('admin/jerarquia/jefes/{jefe}/delegados', [AdminUserJerarquiaController::class, 'syncJefeDelegados']);

    Route::get('admin/mapa-recintos/recintos', [AdminRecintoJefeMapaController::class, 'recintos']);
    Route::get('admin/mapa-recintos/jefes', [AdminRecintoJefeMapaController::class, 'jefes']);
    Route::put('admin/mapa-recintos/recintos/{recinto}/jefe', [AdminRecintoJefeMapaController::class, 'asignar']);

//    Route::get('admin/mesas', [SuperAdminMesasController::class, 'index']);
//    Route::get('admin/mesas/options/recintos', [SuperAdminMesasController::class, 'recintosOptions']);
//    Route::get('admin/mesas/options/delegados', [SuperAdminMesasController::class, 'delegadosOptions']);
//
//    Route::put('admin/mesas/{mesa}/delegado', [SuperAdminMesasController::class, 'asignarDelegado']);

    Route::get('admin/mesas/{mesa}/resultado', [SuperAdminMesasController::class, 'resultado']);
    Route::put('admin/mesas/{mesa}/resultado', [SuperAdminMesasController::class, 'guardarResultado']);
    Route::get('admin/mesas/options/mesas', [SuperAdminMesasController::class, 'mesasOptions']);
    Route::get('admin/mesas/bootstrap', [SuperAdminMesasController::class, 'bootstrap']);
    Route::get('admin/mesas', [SuperAdminMesasController::class, 'index']);
    Route::get('admin/mesas/options/recintos', [SuperAdminMesasController::class, 'recintosOptions']);
    Route::get('admin/mesas/options/mesas', [SuperAdminMesasController::class, 'mesasOptions']); // ✅ NUEVO
    Route::get('admin/mesas/options/delegados', [SuperAdminMesasController::class, 'delegadosOptions']);
    Route::get('admin/mesas-print/asistencia-capacitacion', [SuperAdminMesasController::class, 'printAsistenciaCapacitacion']);
    Route::get('admin/mesas-print/actas', [SuperAdminMesasController::class, 'printActas']);
    Route::put('admin/mesas/{mesa}/delegado', [SuperAdminMesasController::class, 'asignarDelegado']);
    Route::put('admin/mesas/{mesa}/asistencia-capacitacion', [SuperAdminMesasController::class, 'asistenciaCapacitacion']);
    Route::get('admin/mesas/{mesa}/resultado', [SuperAdminMesasController::class, 'resultado']);
    Route::put('admin/mesas/{mesa}/resultado', [SuperAdminMesasController::class, 'guardarResultado']);

    Route::get('admin/ia-control/bootstrap', [MesaAiControlController::class, 'bootstrap']);
    Route::get('admin/ia-control/mesas-options', [MesaAiControlController::class, 'mesasOptions']);
    Route::post('admin/ia-control/process', [MesaAiControlController::class, 'process']);
    Route::post('admin/ia-control/{control}/confirm', [MesaAiControlController::class, 'confirm']);

    // ─── Reportes ─────────────────────────────────────────────────────────────
    Route::get('reportes/delegados-asignados', [ReportesController::class, 'delegadosAsignados']);
    Route::get('reportes/jefes-asignados', [ReportesController::class, 'jefesAsignados']);
    Route::get('reportes/delegados-libres', [ReportesController::class, 'delegadosLibres']);
    Route::get('reportes/jefes-libres', [ReportesController::class, 'jefesLibres']);
    Route::get('reportes/recintos-sin-jefe', [ReportesController::class, 'recintosSinJefe']);
    Route::get('reportes/mesas-libres', [ReportesController::class, 'mesasLibres']);
    Route::get('reportes/export/delegados-asignados', [ReportesController::class, 'exportDelegadosAsignados']);
    Route::get('reportes/export/jefes-asignados', [ReportesController::class, 'exportJefesAsignados']);
    Route::get('reportes/export/delegados-libres', [ReportesController::class, 'exportDelegadosLibres']);
    Route::get('reportes/export/jefes-libres', [ReportesController::class, 'exportJefesLibres']);
    Route::get('reportes/export/recintos-sin-jefe', [ReportesController::class, 'exportRecintosSinJefe']);
    Route::get('reportes/export/mesas-libres', [ReportesController::class, 'exportMesasLibres']);

});
Route::prefix('mobile')->group(function () {
    Route::post('/login', [\App\Http\Controllers\MobileAuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [\App\Http\Controllers\MobileAuthController::class, 'me']);
        Route::get('/asistencia', [\App\Http\Controllers\MobileResultadosController::class, 'asistencia']);
        Route::post('/asistencia/update', [\App\Http\Controllers\MobileResultadosController::class, 'asistenciaUpdate']);
        Route::get('/votacion/catalogo', [\App\Http\Controllers\MobileResultadosController::class, 'votacionCatalogo']);
        Route::get('/votacion/mesa/{mesa}', [\App\Http\Controllers\MobileResultadosController::class, 'votacionMesa']);
        Route::post('/votacion/mesa/{mesa}/guardar', [\App\Http\Controllers\MobileResultadosController::class, 'votacionGuardar']);
        Route::post('/resultados/sync', [\App\Http\Controllers\MobileResultadosController::class, 'sync']);
    });
});
//Route::get(
//    'admin/recintos/geocode',
//    [RecintoMapaController::class, 'geocodeOruro']
//);
