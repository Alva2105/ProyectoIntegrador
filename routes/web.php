<?php

use Illuminate\Support\Facades\Route;
use App\Models\Repuesto;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\TecnicoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\GerenteController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\Services\TipoMantenimientoController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\SeguimientoDashboardController;

/* ============================================================
    RUTAS PÚBLICAS
============================================================ */

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// CAMBIO: antes filtraba por cod_inv (tabla inventarios ya no existe)
// Ahora filtra repuestos por cod_categoria_rep
Route::get('/api/repuestos/{categoria}', function($categoria) {
    return Repuesto::where('cod_categoria_rep', $categoria)->get();
});

// Registro
Route::get('/register', [RegistroController::class, 'showForm'])->name('register');
Route::post('/register', [RegistroController::class, 'registrar'])->name('register.post');

// Login / Logout
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Servicios sin login
Route::get('/servicios/tipo', [TipoMantenimientoController::class, 'index'])->name('servicios.tipo');
Route::get('/servicios/mantPreventivo', [TipoMantenimientoController::class, 'mantPreventivo'])->name('servicios.preventivo');
Route::get('/servicios/mantCorrectivo', [TipoMantenimientoController::class, 'mantCorrectivo'])->name('servicios.correctivo');


/* ============================================================
    RUTAS PROTEGIDAS (LOGIN REQUERIDO)
============================================================ */
Route::middleware(['auth'])->group(function () {

    /* ============================================================
        PERFILES POR ROL
    ============================================================ */

    // Cliente
    Route::get('/cliente/perfil', [PerfilController::class, 'perfilCliente'])
        ->name('cliente.perfil')
        ->middleware('role:Cliente');

    Route::get('/cliente/mis-vehiculos', [ClienteController::class, 'misVehiculos'])
        ->name('cliente.vehiculos')
        ->middleware('role:Cliente');

    Route::get('/cliente/mis-solicitudes', [ClienteController::class, 'misSolicitudes'])
        ->name('cliente.solicitudes')
        ->middleware('role:Cliente');

    // SuperAdmin
    Route::get('/dashboard/perfil', [PerfilController::class, 'perfilAdmin'])
        ->name('dashboard.perfil')
        ->middleware('role:SuperAdmin');

    // Gerente
    Route::get('/gerente/perfil', [PerfilController::class, 'perfilGerente'])
        ->name('gerente.perfil')
        ->middleware('role:Gerente');

    Route::get('/gerente/home', [GerenteController::class, 'home'])
        ->name('gerente.home')
        ->middleware('role:Gerente');

    Route::get('/gerente/inventarios', [GerenteController::class, 'inventarios'])
        ->name('gerente.inventarios')
        ->middleware('role:Gerente');

    Route::get('/gerente/inventario/{cod_categoria}', [GerenteController::class, 'verInventario'])
        ->name('gerente.inventario.ver')
        ->middleware('role:Gerente');

    // CAMBIO: imagen de repuesto deshabilitada (img_rep no existe en nueva BD)
    // Se mantiene la ruta para no romper referencias, pero el controller devuelve error controlado
    Route::post('/gerente/repuesto/{id}/imagen-ajax', [GerenteController::class, 'actualizarImagenRepuestoAjax'])
        ->name('gerente.repuesto.imagenAjax');

    Route::post('/gerente/repuesto/{id}/actualizar-stock-inline', [GerenteController::class, 'actualizarStockInline'])
        ->name('gerente.repuesto.actualizarStockInline')
        ->middleware('role:Gerente');

    Route::get('/gerente/solicitudes', [GerenteController::class, 'solicitudes'])
        ->name('gerente.solicitudes')
        ->middleware('role:Gerente');

    Route::post('/gerente/solicitud/{cod_sol}/aprobar', [GerenteController::class, 'aprobarSolicitud'])
        ->name('gerente.solicitud.aprobar')
        ->middleware('role:Gerente');

    Route::get('/gerente/kardexClientes', [GerenteController::class, 'kardexClientes'])
        ->name('gerente.kardexClientes')
        ->middleware('role:Gerente');

    Route::get('/gerente/listadoTecnicos', [GerenteController::class, 'listadoTecnicos'])
        ->name('gerente.listadoTecnicos')
        ->middleware('role:Gerente');

    Route::get('/gerente/listadoTecnicos/buscar', [GerenteController::class, 'buscarTecnicos'])
        ->name('gerente.listadoTecnicos.buscar')
        ->middleware('role:Gerente');

    // CAMBIO: parámetro {cod_tau} → {cod_usuario} (ya no es TecnicoAutomotriz, es Usuario)
    Route::get('/gerente/tecnico/{cod_usuario}/reportes', [GerenteController::class, 'verReportesTecnico'])
        ->name('gerente.tecnico.reportes')
        ->middleware('role:Gerente');

    // CAMBIO: cod_tau → cod_usuario, cod_man → cod_mantenimiento
    Route::get('/gerente/tecnico/{cod_usuario}/mantenimiento/{cod_mantenimiento}', [GerenteController::class, 'verDetalleReporte'])
        ->name('gerente.mantenimiento.detalle')
        ->middleware('role:Gerente');

    // ELIMINADO: Route enviarReporte (no existe ese método en el GerenteController nuevo)
    // Si lo necesitas, agrégalo al controlador primero

    Route::get('/gerente/notifiGerente', [GerenteController::class, 'notifiGerente'])
        ->name('gerente.notifiGerente')
        ->middleware('role:Gerente');

    // ══════════════════════════════════════════
    // TÉCNICO AUTOMOTRIZ
    // ══════════════════════════════════════════

    Route::get('/asignaciones', [TecnicoController::class, 'asignaciones'])
        ->name('tecnico.asignaciones')
        ->middleware('role:TecnicoAutomotriz');

    Route::patch('/tecnico/asignaciones/{cod}/finalizar', [TecnicoController::class, 'finalizarAsignacion'])
        ->name('tecnico.asignaciones.finalizar')
        ->middleware('role:TecnicoAutomotriz');

    Route::get('/tecnico/finalizados', [TecnicoController::class, 'asignacionesFinalizadas'])
        ->name('tecnico.finalizados')
        ->middleware('role:TecnicoAutomotriz');

    Route::get('/tecnico/seguimiento/{cod_sol}', [TecnicoController::class, 'tablon_seguimiento'])
        ->name('tecnico.seguimiento')
        ->middleware('role:TecnicoAutomotriz');

    Route::get('/tecnico/seguimiento/{cod_sol}/nuevo', [TecnicoController::class, 'nuevoSeguimiento'])
        ->name('tecnico.seguimiento.nuevo')
        ->middleware('role:TecnicoAutomotriz');

    Route::post('/tecnico/seguimiento/guardar', [TecnicoController::class, 'guardarSeguimiento'])
        ->name('tecnico.seguimiento.guardar')
        ->middleware('role:TecnicoAutomotriz');

    Route::get('/technical/perfil', [PerfilController::class, 'perfilTecnico'])
        ->name('tecnico.perfil')
        ->middleware('role:TecnicoAutomotriz');

    Route::get('/tecnico/reportes', [TecnicoController::class, 'reportesTecnico'])
        ->name('tecnico.reportes')
        ->middleware('role:TecnicoAutomotriz');

    // Editar seguimiento
    Route::get('/seguimiento/{id}/edit',   [TecnicoController::class, 'edit'])
        ->name('tecnico.seguimiento.edit');
    Route::put('/seguimiento/{id}',        [TecnicoController::class, 'update'])
        ->name('tecnico.seguimiento.update');
    // Editar repuestos de un seguimiento
    Route::get   ('/seguimiento/{cod_seg}/repuestos',        [TecnicoController::class, 'editarRepuestos'])
        ->name('tecnico.seguimiento.repuestos');
    Route::post   ('/seguimiento/{cod_seg}/repuestos/update',        [TecnicoController::class, 'actualizarRepuestos'])
        ->name('tecnico.seguimiento.repuestos.update');

    // Borrado lógico
    Route::delete('/seguimiento/{id}',     [TecnicoController::class, 'destroy'])
        ->name('tecnico.seguimiento.destroy');

    Route::post  ('/seguimiento/{cod_seg}/restore', [TecnicoController::class, 'restore'])
     ->name('tecnico.seguimiento.restore');

    /* ============================================================
        SUBIR IMAGEN DE PERFIL (todos los roles)
    ============================================================ */
    Route::post('/perfil/imagen', [PerfilController::class, 'actualizarImagen'])
        ->name('perfil.actualizarImagen');

    /* ============================================================
        VEHÍCULOS (solo Cliente)
    ============================================================ */
    Route::get('/vehiculos/registrar', [VehiculoController::class, 'create'])
        ->name('vehiculos.registrar')
        ->middleware('role:Cliente');

    Route::post('/vehiculos/guardar', [VehiculoController::class, 'store'])
        ->name('vehiculos.store')
        ->middleware('role:Cliente');

    /* ============================================================
        SOLICITUDES (Cliente)
    ============================================================ */
    Route::prefix('solicitudes')->group(function () {

        Route::get('/crear', [SolicitudController::class, 'crear'])
            ->name('solicitud.crear');

        Route::post('/enviar', [SolicitudController::class, 'enviar'])
            ->name('solicitud.enviar');

        Route::get('/mis-solicitudes', [SolicitudController::class, 'listarCliente'])
            ->name('solicitud.misSolicitudes')
            ->middleware('role:Cliente');
    });

    /* ============================================================
        DASHBOARD (SuperAdmin)
    ============================================================ */
    Route::middleware(['role:SuperAdmin'])->prefix('dashboard')->group(function () {

        // CAMBIO: antes redirigía a dashboard.usuarios, ahora muestra el panel con stats reales
        Route::get('/', [SuperAdminController::class, 'index'])->name('dashboard');

        /* --- Usuarios --- */
        Route::prefix('usuarios')->group(function () {
            Route::get('/', [RegistroController::class, 'listar'])->name('dashboard.usuarios');
            Route::get('/clientes', [RegistroController::class, 'listarClientes'])->name('usuarios.clientes');
            Route::get('/tecnicos', [RegistroController::class, 'listarTecnicos'])->name('usuarios.tecnicos');
            // ELIMINADO: /conductores (rol Conductor ya no existe)
            Route::get('/buscar', [RegistroController::class, 'buscar'])->name('usuarios.buscar');
            // CAMBIO: actualizarEstado devuelve error controlado (est_usu no existe en nueva BD)
            Route::put('/{id}/estado', [RegistroController::class, 'actualizarEstado'])->name('usuarios.actualizarEstado');
            Route::post('/guardar', [RegistroController::class, 'guardarUsuario'])->name('usuarios.guardar');
            Route::put('/{id}/actualizar', [RegistroController::class, 'actualizarUsuario'])->name('usuarios.actualizar');
            Route::delete('/{id}/eliminar', [RegistroController::class, 'eliminarUsuario'])->name('usuarios.eliminar');
            Route::get('/exportar/excel', [RegistroController::class, 'exportarExcel'])->name('usuarios.exportar.excel');
            Route::get('/exportar/pdf', [RegistroController::class, 'exportarPDF'])->name('usuarios.exportar.pdf');
        });

        /* --- Vehículos --- */
        Route::prefix('vehiculos')->group(function () {
            Route::get('/', [VehiculoController::class, 'listarVehiculos'])->name('dashboard.vehiculos');
            Route::get('/buscar', [VehiculoController::class, 'buscar'])->name('vehiculos.buscar');
            // CAMBIO: actualizarEstado devuelve error controlado (est_veh no existe en nueva BD)
            Route::put('/{id}/estado', [VehiculoController::class, 'actualizarEstado'])->name('vehiculos.actualizarEstado');
            Route::post('/guardar', [VehiculoController::class, 'guardar'])->name('vehiculos.guardar');
            Route::put('/{codVehiculo}/{codCliente}/actualizar', [VehiculoController::class, 'actualizar'])->name('vehiculos.actualizar');
            Route::delete('/{codVehiculo}/{codCliente}/eliminar', [VehiculoController::class, 'eliminar'])->name('vehiculos.eliminar');
        });

        /* --- Mantenimientos --- */
        Route::prefix('mantenimientos')->group(function () {
            Route::get('/', [MantenimientoController::class, 'listar'])->name('dashboard.mantenimientos');
            Route::get('/preventivos', [MantenimientoController::class, 'listarPreventivos'])->name('mantenimientos.preventivos');
            Route::get('/correctivos', [MantenimientoController::class, 'listarCorrectivos'])->name('mantenimientos.correctivos');
            Route::get('/buscar', [MantenimientoController::class, 'buscar'])->name('mantenimientos.buscar');
            Route::post('/guardar', [MantenimientoController::class, 'guardar'])->name('mantenimientos.guardar');
            Route::post('/orden/guardar', [MantenimientoController::class, 'guardarOrden'])->name('mantenimientos.orden.guardar'); // si lo tenías
            Route::put('/{id}/actualizar', [MantenimientoController::class, 'actualizar'])->name('mantenimientos.actualizar');
            Route::put('/{id}/estado', [MantenimientoController::class, 'actualizarEstado'])->name('mantenimientos.estado');
            Route::get('/{id}/orden', [MantenimientoController::class, 'verOrden'])->name('mantenimientos.orden.ver');               // NUEVO — Ver Orden (AJAX JSON)
            Route::put('/{id}/orden/actualizar', [MantenimientoController::class, 'actualizarOrden'])->name('mantenimientos.orden.actualizar'); // NUEVO — Editar Orden
            Route::delete('/{id}/eliminar', [MantenimientoController::class, 'eliminar'])->name('mantenimientos.eliminar');
            Route::post('/{id}/restaurar', [MantenimientoController::class, 'restaurar'])->name('mantenimientos.restaurar');
            Route::delete('/{id}/orden/servicio/{codServicio}', [MantenimientoController::class, 'eliminarServicioOrden'])->name('mantenimientos.orden.servicio.eliminar');
            Route::delete('/{id}/orden/repuesto/{codRepuesto}', [MantenimientoController::class, 'eliminarRepuestoOrden'])->name('mantenimientos.orden.repuesto.eliminar');
            Route::get('/reporte', [MantenimientoController::class, 'reporte'])->name('mantenimientos.reporte');
        });

        /* --- Solicitudes --- */
        Route::prefix('solicitudes')->group(function () {
            // ─ Ya existían ─
            Route::get('/', [SolicitudController::class, 'listar'])->name('dashboard.solicitudes');
            Route::get('/ver/{id}', [SolicitudController::class, 'ver'])->name('dashboard.solicitudes.ver');
            Route::put('/{id}/estado', [SolicitudController::class, 'actualizarEstado'])->name('dashboard.solicitudes.estado');

            // ─ NUEVAS (necesarias para el modal ABM) ─
            // ⚠️ vehiculos/{cod} debe ir ANTES de {id} para no colisionar
            Route::get('/vehiculos/{codCliente}', [SolicitudController::class, 'vehiculosPorCliente'])->name('solicitudes.vehiculos');
            Route::post('/guardar', [SolicitudController::class, 'guardar'])->name('solicitudes.guardar');
            Route::put('/{id}/actualizar', [SolicitudController::class, 'actualizar'])->name('solicitudes.actualizar');
            Route::delete('/{id}/eliminar',[SolicitudController::class, 'eliminar'])->name('solicitudes.eliminar');
            Route::post('/{id}/restaurar',[SolicitudController::class, 'restaurar'])->name('solicitudes.restaurar');
            Route::get('/reporte', [SolicitudController::class, 'reporte'])->name('solicitudes.reporte');
            Route::post('/asignaciones/guardar', [AsignacionController::class, 'guardar'])->name('asignaciones.guardar');
            Route::get('/asignaciones/solicitud/{id}', [AsignacionController::class, 'porSolicitud'])->name('asignaciones.porSolicitud');
        });

            Route::prefix('dashboard/seguimientos')->group(function () {
                Route::get('/',                  [SeguimientoDashboardController::class, 'index'])->name('dashboard.seguimientos');
                Route::get('/reporte',           [SeguimientoDashboardController::class, 'reporte'])->name('seguimientos.reporte');
                Route::get('/{cod_seg}/detalle', [SeguimientoDashboardController::class, 'detalle'])->name('dashboard.seguimientos.detalle');
                Route::post('/{cod_seg}/eliminar',  [SeguimientoDashboardController::class, 'eliminar'])->name('dashboard.seguimientos.eliminar');
                Route::post('/{cod_seg}/restaurar', [SeguimientoDashboardController::class, 'restaurar'])->name('dashboard.seguimientos.restaurar');
            });
    });

});