@extends('layouts.dashboard')

@section('contenido')
@if ($errors->any())
    <div style="background:#dc3545;color:#fff;padding:14px 20px;border-radius:8px;margin-bottom:16px;">
        <strong>Errores de validación:</strong>
        <ul style="margin:8px 0 0 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('error'))
    <div style="background:#ff7b00;color:#fff;padding:14px 20px;border-radius:8px;margin-bottom:16px;">
        <strong>Error:</strong> {{ session('error') }}
    </div>
@endif

<div class="Dmant-content">

    <div class="header-content">
        <h2>
            Mantenimientos
            <button class="btn-nuevoUsu" onclick="abrirModalMantenimiento()">+ Nuevo Mantenimiento</button>
            <a href="{{ route('mantenimientos.reporte') }}" class="btn-reporte">
                <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">summarize</span>
                Reporte
            </a>
        </h2>
    </div>

    <div class="tabs-seccion">
        <button class="tab-btn active" onclick="cambiarTab('activos', this)">
            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">build</span>
            Mantenimientos activos
            <span class="tab-count">{{ $mantenimientos->total() }}</span>
        </button>
        <button class="tab-btn" onclick="cambiarTab('papelera', this)">
            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">delete</span>
            Papelera
            <span class="tab-count {{ $eliminados->count() > 0 ? 'has-items' : '' }}">
                {{ $eliminados->count() }}
            </span>
        </button>
    </div>

    <div id="seccion-activos">

        <div class="filtros-estado">
            <button class="filtro-btn active" data-estado="TODOS">Todos</button>
            <button class="filtro-btn" data-estado="EN_PROCESO">En Proceso</button>
            <button class="filtro-btn" data-estado="VERIFICACION">Verificación</button>
            <button class="filtro-btn" data-estado="FINALIZADO">Finalizado</button>
        </div>

        <div class="search-container">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Buscar por cliente, vehículo...">
                <div class="search-icon">
                    <span class="material-symbols-outlined buscador">search</span>
                </div>
            </div>
        </div>

        <div class="table-wrapper">
            <table id="tablaMantenimientos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Vehículo</th>
                        <th>Tipo</th>
                        <th>F. Inicio</th>
                        <th>F. Fin</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mantenimientos as $m)
                        @php
                            $sol      = $m->solicitud;
                            $cliente  = $sol?->cliente;
                            $vehiculo = $sol?->vehiculo;
                            $tipo_man = $sol?->tma_sol ?? '—';
                        @endphp
                        <tr data-id="{{ $m->cod_mantenimientos }}">
                            <td class="id-usuario">
                                <span class="material-symbols-outlined icono-perfil">build</span>
                                {{ $m->cod_mantenimientos }}
                            </td>
                            <td>
                                @if($cliente)
                                    <div class="perfil-conductor">
                                        <span class="material-symbols-outlined icono-perfil">account_circle</span>
                                        <span>{{ $cliente->nom_cli }} {{ $cliente->app_cli }}</span>
                                    </div>
                                @else
                                    <span style="color:#777;">Sin cliente</span>
                                @endif
                            </td>
                            <td>
                                @if($vehiculo)
                                    <div class="perfil-conductor">
                                        <span class="material-symbols-outlined icono-perfil">directions_car</span>
                                        <span>
                                            {{ $vehiculo->mar_veh }} {{ $vehiculo->mod_veh }}
                                            @if($vehiculo->ani_veh)({{ $vehiculo->ani_veh }})@endif
                                        </span>
                                    </div>
                                @else
                                    <span style="color:#777;">Sin vehículo</span>
                                @endif
                            </td>
                            <td><span class="badge-tipo">{{ $tipo_man }}</span></td>
                            <td>{{ $m->fec_ini_man ? \Carbon\Carbon::parse($m->fec_ini_man)->format('d/m/Y') : '—' }}</td>
                            <td>{{ $m->fec_fin_man ? \Carbon\Carbon::parse($m->fec_fin_man)->format('d/m/Y') : '—' }}</td>
                            <td>
                                @if($m->total_man > 0)
                                    <span style="color:#4caf50;font-weight:700;">
                                        Bs {{ number_format($m->total_man, 2) }}
                                    </span>
                                @else
                                    <span style="color:#777;">—</span>
                                @endif
                            </td>
                            <td class="acciones">
                                <button type="button" class="btn-ver-orden"
                                        onclick="verOrden('{{ $m->cod_mantenimientos }}')">
                                    <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">visibility</span>
                                    Ver
                                </button>
                                <button type="button" class="btn-editar-orden"
                                        onclick="abrirEditarOrden('{{ $m->cod_mantenimientos }}')">
                                    <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">edit_note</span>
                                    Orden
                                </button>
                                <button type="button" class="btn-eliminar"
                                        onclick="confirmarEliminar(
                                            '{{ $m->cod_mantenimientos }}',
                                            '{{ addslashes(($cliente->nom_cli ?? '').'' .($cliente->app_cli ?? '')) }}'
                                        )">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;color:#aaa;padding:30px;">
                                No hay mantenimientos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($mantenimientos->hasPages())
        <div class="pagination-container">
            @if ($mantenimientos->onFirstPage())
                <span class="page-btn disabled">&#10094;</span>
            @else
                <a href="{{ $mantenimientos->previousPageUrl() }}" class="page-btn">&#10094;</a>
            @endif
            @if ($mantenimientos->currentPage() > 3)
                <a href="{{ $mantenimientos->url(1) }}" class="page-btn">1</a>
                <span class="page-dots">...</span>
            @endif
            @for ($i = max(1,$mantenimientos->currentPage()-2);
                  $i <= min($mantenimientos->lastPage(),$mantenimientos->currentPage()+2); $i++)
                @if ($i == $mantenimientos->currentPage())
                    <span class="page-btn active">{{ $i }}</span>
                @else
                    <a href="{{ $mantenimientos->url($i) }}" class="page-btn">{{ $i }}</a>
                @endif
            @endfor
            @if ($mantenimientos->currentPage() < $mantenimientos->lastPage() - 2)
                <span class="page-dots">...</span>
                <a href="{{ $mantenimientos->url($mantenimientos->lastPage()) }}" class="page-btn">{{ $mantenimientos->lastPage() }}</a>
            @endif
            @if ($mantenimientos->hasMorePages())
                <a href="{{ $mantenimientos->nextPageUrl() }}" class="page-btn">&#10095;</a>
            @else
                <span class="page-btn disabled">&#10095;</span>
            @endif
        </div>
        @endif

    </div>

    <div id="seccion-papelera" style="display:none;">
        <div class="papelera-header">
            <span class="material-symbols-outlined" style="color:#dc3545;font-size:22px;">delete_sweep</span>
            <span>Mantenimientos eliminados — pueden restaurarse en cualquier momento.</span>
        </div>
        @if($eliminados->isEmpty())
            <div style="text-align:center;color:#aaa;padding:50px 0;">
                <span class="material-symbols-outlined" style="font-size:52px;display:block;margin-bottom:10px;opacity:.3;">delete_outline</span>
                La papelera está vacía.
            </div>
        @else
            <div class="table-wrapper">
                <table id="tablaPapelera">
                    <thead>
                        <tr>
                            <th>ID</th><th>Cliente</th><th>Vehículo</th><th>Tipo</th>
                            <th>F. Inicio</th><th>Eliminado el</th><th>Restaurado el</th><th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($eliminados as $e)
                            @php
                                $sol_e = $e->solicitud;
                                $cli_e = $sol_e?->cliente;
                                $veh_e = $sol_e?->vehiculo;
                                $tip_e = $sol_e?->tma_sol ?? '—';
                            @endphp
                            <tr data-id="{{ $e->cod_mantenimientos }}">
                                <td class="id-usuario">
                                    <span class="material-symbols-outlined icono-perfil" style="color:#666;">build_circle</span>
                                    {{ $e->cod_mantenimientos }}
                                </td>
                                <td>
                                    @if($cli_e)
                                        <div class="perfil-conductor">
                                            <span class="material-symbols-outlined icono-perfil">account_circle</span>
                                            <span>{{ $cli_e->nom_cli }} {{ $cli_e->app_cli }}</span>
                                        </div>
                                    @else
                                        <span style="color:#777;">Sin cliente</span>
                                    @endif
                                </td>
                                <td>
                                    @if($veh_e)
                                        <div class="perfil-conductor">
                                            <span class="material-symbols-outlined icono-perfil">directions_car</span>
                                            <span>{{ $veh_e->mar_veh }} {{ $veh_e->mod_veh }}</span>
                                        </div>
                                    @else
                                        <span style="color:#777;">Sin vehículo</span>
                                    @endif
                                </td>
                                <td><span class="badge-tipo">{{ $tip_e }}</span></td>
                                <td>{{ $e->fec_ini_man ? \Carbon\Carbon::parse($e->fec_ini_man)->format('d/m/Y') : '—' }}</td>
                                <td><span style="color:#e57373;font-size:12px;">{{ \Carbon\Carbon::parse($e->deleted_at)->format('d/m/Y H:i') }}</span></td>
                                <td>
                                    @if($e->restored_at)
                                        <span style="color:#4caf50;font-size:12px;">{{ \Carbon\Carbon::parse($e->restored_at)->format('d/m/Y H:i') }}</span>
                                    @else
                                        <span style="color:#555;font-size:12px;">—</span>
                                    @endif
                                </td>
                                <td class="acciones">
                                    <button type="button" class="btn-restaurar"
                                            onclick="confirmarRestaurar('{{ $e->cod_mantenimientos }}','{{ addslashes(($cli_e->nom_cli??'').'' .($cli_e->app_cli??'')) }}')">
                                        <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">restore</span>
                                        Restaurar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

{{-- MODAL CONFIRMAR ELIMINACIÓN --}}
<div id="modalConfirmarEliminar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:3000;justify-content:center;align-items:center;">
    <div class="modal-confirm-box">
        <div class="modal-confirm-icon">
            <span class="material-symbols-outlined">delete_forever</span>
        </div>
        <h3 class="modal-confirm-titulo">¿Eliminar mantenimiento?</h3>
        <p class="modal-confirm-texto">
            El mantenimiento de <strong id="confirmNombreCliente">—</strong>
            se moverá a la papelera.<br>
            <span style="color:#aaa;font-size:13px;">Podrás restaurarlo desde la pestaña "Papelera".</span>
        </p>
        <div class="modal-confirm-botones">
            <button class="btn-confirm-cancelar" onclick="cerrarModalConfirmar()">Cancelar</button>
            <button class="btn-confirm-eliminar" onclick="ejecutarEliminar()">Sí, eliminar</button>
        </div>
        <form id="formEliminar" method="POST" style="display:none;">@csrf @method('DELETE')</form>
    </div>
</div>

{{-- MODAL CONFIRMAR RESTAURACIÓN --}}
<div id="modalConfirmarRestaurar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:3000;justify-content:center;align-items:center;">
    <div class="modal-confirm-box">
        <div class="modal-confirm-icon restaurar">
            <span class="material-symbols-outlined">restore</span>
        </div>
        <h3 class="modal-confirm-titulo">¿Restaurar mantenimiento?</h3>
        <p class="modal-confirm-texto">
            El mantenimiento de <strong id="confirmNombreRestaurar">—</strong>
            volverá a estar activo.
        </p>
        <div class="modal-confirm-botones">
            <button class="btn-confirm-cancelar" onclick="cerrarModalRestaurar()">Cancelar</button>
            <button class="btn-confirm-restaurar" onclick="ejecutarRestaurar()">Sí, restaurar</button>
        </div>
        <form id="formRestaurar" method="POST" style="display:none;">@csrf</form>
    </div>
</div>

{{-- MODAL NUEVO MANTENIMIENTO --}}
<div id="modalMantenimiento" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:1000;justify-content:center;align-items:center;">
    <div class="modal-mant-box">
        <div class="modal-mant-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="material-symbols-outlined" style="color:#ff7b00;font-size:26px;">build_circle</span>
                <h2 id="tituloModalMant" style="margin:0;color:#ff7b00;font-size:18px;font-weight:700;">Nueva Orden de Trabajo</h2>
            </div>
            <span onclick="cerrarModalMantenimiento()" style="font-size:26px;cursor:pointer;color:#ccc;">&times;</span>
        </div>

        <form id="formMantenimiento" action="{{ route('mantenimientos.guardar') }}" method="POST">
            @csrf
            <input type="hidden" id="methodMant" name="_method" value="POST">

            <div class="mant-seccion-titulo">
                <span class="material-symbols-outlined" style="color:#ff7b00;font-size:18px;vertical-align:middle;">assignment</span>
                Solicitud de origen
            </div>
            <div class="form-grid-2">
                <div class="form-group" style="grid-column:1/3;">
                    <label>Solicitud finalizada <span style="color:#dc3545;">*</span></label>
                    <select name="cod_solicitudes_man" id="selectSolicitud" required onchange="cargarDatosSolicitud(this)">
                        <option value="">Seleccione una solicitud finalizada...</option>
                        @foreach($solicitudesFinalizadas as $sol)
                            <option value="{{ $sol->cod_solicitudes }}"
                                    data-cliente="{{ $sol->cliente?->nom_cli }} {{ $sol->cliente?->app_cli }}"
                                    data-telefono="{{ $sol->cliente?->tel_cli ?? '' }}"
                                    data-vehiculo="{{ $sol->vehiculo?->mar_veh }} {{ $sol->vehiculo?->mod_veh }}"
                                    data-anio="{{ $sol->vehiculo?->ani_veh ?? '' }}"
                                    data-placa="{{ $sol->vehiculo?->cod_vehiculos ?? '' }}"
                                    data-tipo="{{ $sol->tma_sol }}"
                                    data-servicio="{{ $sol->ser_sol ?? '' }}">
                                {{ $sol->cod_solicitudes }} —
                                {{ $sol->cliente?->nom_cli }} {{ $sol->cliente?->app_cli }} —
                                {{ $sol->vehiculo?->mar_veh }} {{ $sol->vehiculo?->mod_veh }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="tarjetaSolicitud" style="display:none;" class="tarjeta-solicitud">
                <div class="tarjeta-fila">
                    <div class="tarjeta-item"><span class="tarjeta-label">Cliente</span><span class="tarjeta-valor" id="ts_cliente">—</span></div>
                    <div class="tarjeta-item"><span class="tarjeta-label">Teléfono</span><span class="tarjeta-valor" id="ts_telefono">—</span></div>
                    <div class="tarjeta-item"><span class="tarjeta-label">Vehículo</span><span class="tarjeta-valor" id="ts_vehiculo">—</span></div>
                    <div class="tarjeta-item"><span class="tarjeta-label">Año</span><span class="tarjeta-valor" id="ts_anio">—</span></div>
                    <div class="tarjeta-item"><span class="tarjeta-label">Placa</span><span class="tarjeta-valor" id="ts_placa">—</span></div>
                    <div class="tarjeta-item"><span class="tarjeta-label">Tipo</span><span class="tarjeta-valor" id="ts_tipo">—</span></div>
                </div>
                <div id="ts_servicio_row" style="display:none;" class="tarjeta-fila">
                    <div class="tarjeta-item" style="flex:1;"><span class="tarjeta-label">Servicio solicitado</span><span class="tarjeta-valor" id="ts_servicio">—</span></div>
                </div>
            </div>

            <div class="separador"></div>

            <div class="mant-seccion-titulo">
                <span class="material-symbols-outlined" style="color:#ff7b00;font-size:18px;vertical-align:middle;">tune</span>
                Datos del mantenimiento
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Fecha inicio <span style="color:#dc3545;">*</span></label>
                    <input type="datetime-local" name="fec_ini_man" required>
                </div>
                <div class="form-group">
                    <label>Fecha fin</label>
                    <input type="datetime-local" name="fec_fin_man">
                </div>
            </div>

            <div class="separador"></div>

            <div class="mant-seccion-titulo">
                <span class="material-symbols-outlined" style="color:#ff7b00;font-size:18px;vertical-align:middle;">design_services</span>
                Servicios realizados
            </div>
            <div class="tabla-items-header">
                <span style="flex:2;">Servicio</span>
                <span style="width:80px;text-align:center;">Cant.</span>
                <span style="width:120px;text-align:right;">P. Unitario</span>
                <span style="width:120px;text-align:right;">Subtotal</span>
                <span style="width:36px;"></span>
            </div>
            <div id="listaServicios"></div>
            <button type="button" class="btn-agregar-item" onclick="agregarServicio()">
                <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">add_circle</span>
                Agregar servicio
            </button>

            <div class="separador"></div>

            <div class="mant-seccion-titulo">
                <span class="material-symbols-outlined" style="color:#ff7b00;font-size:18px;vertical-align:middle;">settings</span>
                Repuestos utilizados
            </div>
            <div class="tabla-items-header">
                <span style="flex:2;">Repuesto</span>
                <span style="width:80px;text-align:center;">Cant.</span>
                <span style="width:120px;text-align:right;">P. Unitario</span>
                <span style="width:120px;text-align:right;">Subtotal</span>
                <span style="width:36px;"></span>
            </div>
            <div id="listaRepuestos"></div>
            <button type="button" class="btn-agregar-item" onclick="agregarRepuesto()">
                <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">add_circle</span>
                Agregar repuesto
            </button>

            <div class="separador"></div>

            <div class="total-box">
                <span class="total-label">Total estimado</span>
                <span class="total-valor" id="totalGeneral">Bs 0.00</span>
                <input type="hidden" name="total_man" id="totalHidden" value="0">
            </div>

            <div class="form-group" style="margin-top:14px;">
                <label>Descripción / Observaciones</label>
                <textarea name="des_man" rows="2" placeholder="Detalles del trabajo..."></textarea>
            </div>

            <div class="modal-botones" style="margin-top:20px;">
                <button type="submit" class="btn-guardar-modal">
                    <span class="material-symbols-outlined" style="font-size:15px;vertical-align:middle;">check_circle</span>
                    Guardar
                </button>
                <button type="button" class="btn-cancelar-modal" onclick="cerrarModalMantenimiento()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL VER ORDEN (solo lectura) --}}
<div id="modalVerOrden" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:2000;justify-content:center;align-items:center;">
    <div class="modal-ver-box">
        <div class="modal-mant-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="material-symbols-outlined" style="color:#ff7b00;font-size:24px;">visibility</span>
                <h2 style="margin:0;color:#ff7b00;font-size:18px;font-weight:700;">
                    Orden de Trabajo — <span id="ver_cod">—</span>
                </h2>
            </div>
            <span onclick="cerrarVerOrden()" style="font-size:26px;cursor:pointer;color:#ccc;">&times;</span>
        </div>

        <div id="ver_loading" style="text-align:center;padding:40px;color:#aaa;">
            <span class="material-symbols-outlined" style="font-size:40px;display:block;margin-bottom:10px;animation:spin 1s linear infinite;">refresh</span>
            Cargando...
        </div>

        <div id="ver_contenido" style="display:none;">
            <div class="tarjeta-solicitud" id="ver_tarjeta"></div>
            <div class="separador"></div>

            <div class="mant-seccion-titulo">
                <span class="material-symbols-outlined" style="color:#ff7b00;font-size:18px;vertical-align:middle;">design_services</span>
                Servicios realizados
            </div>
            <div class="tabla-items-header">
                <span style="flex:2;">Servicio</span>
                <span style="width:80px;text-align:center;">Cant.</span>
                <span style="width:120px;text-align:right;">P. Unitario</span>
                <span style="width:120px;text-align:right;">Subtotal</span>
            </div>
            <div id="ver_servicios"></div>

            <div class="separador"></div>

            <div class="mant-seccion-titulo">
                <span class="material-symbols-outlined" style="color:#ff7b00;font-size:18px;vertical-align:middle;">settings</span>
                Repuestos utilizados
            </div>
            <div class="tabla-items-header">
                <span style="flex:2;">Repuesto</span>
                <span style="width:80px;text-align:center;">Cant.</span>
                <span style="width:120px;text-align:right;">P. Unitario</span>
                <span style="width:120px;text-align:right;">Subtotal</span>
            </div>
            <div id="ver_repuestos"></div>

            <div class="separador"></div>

            <div class="total-box">
                <span class="total-label">Total</span>
                <span class="total-valor" id="ver_total">Bs 0.00</span>
            </div>

            <div id="ver_desc_wrap" style="margin-top:14px;display:none;">
                <p style="color:#888;font-size:12px;text-transform:uppercase;letter-spacing:.5px;margin:0 0 4px;">Descripción</p>
                <p id="ver_desc" style="color:#ccc;font-size:14px;margin:0;"></p>
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:20px;">
 
            {{-- Botón Facturar (visible solo si NO tiene factura aún) --}}
            <div id="ver_acciones_fac">
                {{-- Se rellena dinámicamente por JS con verOrden() --}}
            </div>
        
            <button class="btn-cancelar-modal" onclick="cerrarVerOrden()">Cerrar</button>
        </div>
    </div>
</div>

{{-- MODAL EDITAR ORDEN (nuevo editar completo) --}}
<div id="modalEditarOrden" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:2000;justify-content:center;align-items:center;">
    <div class="modal-mant-box">
        <div class="modal-mant-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="material-symbols-outlined" style="color:#ff7b00;font-size:24px;">edit_note</span>
                <h2 style="margin:0;color:#ff7b00;font-size:18px;font-weight:700;">
                    Editar Orden — <span id="edit_cod">—</span>
                </h2>
            </div>
            <span onclick="cerrarEditarOrden()" style="font-size:26px;cursor:pointer;color:#ccc;">&times;</span>
        </div>

        <div id="edit_loading" style="text-align:center;padding:40px;color:#aaa;">
            <span class="material-symbols-outlined" style="font-size:40px;display:block;margin-bottom:10px;animation:spin 1s linear infinite;">refresh</span>
            Cargando...
        </div>

        <div id="edit_contenido" style="display:none;">
            <form id="formEditarOrden" method="POST">
                @csrf
                @method('PUT')

                {{-- Fechas --}}
                <div class="mant-seccion-titulo">
                    <span class="material-symbols-outlined" style="color:#ff7b00;font-size:18px;vertical-align:middle;">tune</span>
                    Datos del mantenimiento
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Fecha inicio <span style="color:#dc3545;">*</span></label>
                        <input type="datetime-local" id="edit_fec_ini" name="fec_ini_man" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha fin</label>
                        <input type="datetime-local" id="edit_fec_fin" name="fec_fin_man">
                    </div>
                </div>

                <div class="separador"></div>

                <div class="mant-seccion-titulo">
                    <span class="material-symbols-outlined" style="color:#ff7b00;font-size:18px;vertical-align:middle;">design_services</span>
                    Servicios realizados
                </div>
                <div class="tabla-items-header">
                    <span style="flex:2;">Servicio</span>
                    <span style="width:80px;text-align:center;">Cant.</span>
                    <span style="width:120px;text-align:right;">P. Unitario</span>
                    <span style="width:120px;text-align:right;">Subtotal</span>
                    <span style="width:36px;"></span>
                </div>
                <div id="edit_listaServicios"></div>
                <button type="button" class="btn-agregar-item" onclick="editAgregarServicio()">
                    <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">add_circle</span>
                    Agregar servicio
                </button>

                <div class="separador"></div>

                <div class="mant-seccion-titulo">
                    <span class="material-symbols-outlined" style="color:#ff7b00;font-size:18px;vertical-align:middle;">settings</span>
                    Repuestos utilizados
                </div>
                <div class="tabla-items-header">
                    <span style="flex:2;">Repuesto</span>
                    <span style="width:80px;text-align:center;">Cant.</span>
                    <span style="width:120px;text-align:right;">P. Unitario</span>
                    <span style="width:120px;text-align:right;">Subtotal</span>
                    <span style="width:36px;"></span>
                </div>
                <div id="edit_listaRepuestos"></div>
                <button type="button" class="btn-agregar-item" onclick="editAgregarRepuesto()">
                    <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">add_circle</span>
                    Agregar repuesto
                </button>

                <div class="separador"></div>

                <div class="total-box">
                    <span class="total-label">Total estimado</span>
                    <span class="total-valor" id="edit_total">Bs 0.00</span>
                    <input type="hidden" name="total_man" id="edit_totalHidden" value="0">
                </div>

                <div class="form-group" style="margin-top:14px;">
                    <label>Descripción / Observaciones</label>
                    <textarea id="edit_des_man" name="des_man" rows="2" placeholder="Detalles del trabajo..."></textarea>
                </div>

                <div class="modal-botones" style="margin-top:20px;">
                    <button type="submit" class="btn-guardar-modal">
                        <span class="material-symbols-outlined" style="font-size:15px;vertical-align:middle;">check_circle</span>
                        Guardar cambios
                    </button>
                    <button type="button" class="btn-cancelar-modal" onclick="cerrarEditarOrden()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
@keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
.tabs-seccion { display:flex; gap:4px; margin-bottom:20px; border-bottom:2px solid #3a3a3a; }
.tab-btn { display:flex; align-items:center; gap:7px; padding:9px 20px; background:transparent; border:none; border-bottom:3px solid transparent; color:#aaa; cursor:pointer; font-size:14px; font-weight:500; margin-bottom:-2px; transition:all 0.2s; }
.tab-btn:hover { color:#fff; }
.tab-btn.active { color:#ff7b00; border-bottom-color:#ff7b00; }
.tab-count { background:#3a3a3a; color:#ccc; padding:1px 7px; border-radius:10px; font-size:11px; font-weight:600; }
.tab-count.has-items { background:#dc3545; color:#fff; }
.papelera-header { display:flex; align-items:center; gap:10px; background:rgba(220,53,69,0.08); border:1px solid rgba(220,53,69,0.25); border-radius:8px; padding:10px 16px; margin-bottom:16px; color:#f08090; font-size:13px; }
.filtros-estado { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
.filtro-btn { padding:6px 18px; border-radius:20px; border:1px solid #555; background:#2a2a2a; color:#ccc; cursor:pointer; font-size:13px; transition:all 0.2s; }
.filtro-btn:hover { border-color:#ff7b00; color:#ff7b00; }
.filtro-btn.active { background:#ff7b00; color:#fff; border-color:#ff7b00; font-weight:bold; }
.badge-tipo { padding:3px 10px; border-radius:8px; font-size:12px; font-weight:600; border:1px solid #ff7b00; color:#ff7b00; background:transparent; white-space:nowrap; }
.estado { padding:4px 10px; border-radius:10px; font-size:12px; font-weight:600; white-space:nowrap; }
.estado.en-proceso { background-color:#ffc107; color:#000; }
.estado.verificacion { background-color:#17a2b8; color:#fff; }
.estado.finalizado { background-color:#28a745; color:#fff; }
.estado.pendiente { background-color:#6c757d; color:#fff; }
.btn-ver-orden { background:transparent; color:#17a2b8; border:1px solid #17a2b8; padding:5px 10px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:4px; transition:all 0.2s; }
.btn-ver-orden:hover { background:#17a2b8; color:#fff; }
.btn-editar-orden { background:transparent; color:#9c6fe4; border:1px solid #9c6fe4; padding:5px 10px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:4px; transition:all 0.2s; }
.btn-editar-orden:hover { background:#9c6fe4; color:#fff; }
.btn-eliminar { background:transparent; color:#E24B4A; border:1px solid #E24B4A; padding:5px 10px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:4px; transition:all 0.2s; }
.btn-eliminar:hover { background:#E24B4A; color:#fff; }
.btn-restaurar { background:#1a3a26; color:#4caf50; border:1px solid #2d6a3a; padding:5px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:4px; transition:all 0.2s; }
.btn-restaurar:hover { background:#28a745; color:#fff; border-color:#28a745; }
.modal-confirm-box { background:#2a2a2a; border:2px solid #444; border-radius:12px; padding:36px 40px; width:420px; max-width:94vw; text-align:center; }
.modal-confirm-icon { width:64px; height:64px; border-radius:50%; background:rgba(220,53,69,0.12); border:2px solid rgba(220,53,69,0.35); display:flex; align-items:center; justify-content:center; margin:0 auto 18px; }
.modal-confirm-icon .material-symbols-outlined { font-size:32px; color:#dc3545; }
.modal-confirm-icon.restaurar { background:rgba(40,167,69,0.12); border-color:rgba(40,167,69,0.35); }
.modal-confirm-icon.restaurar .material-symbols-outlined { color:#28a745; }
.modal-confirm-titulo { color:#fff; font-size:18px; margin:0 0 12px; font-weight:600; }
.modal-confirm-texto { color:#ccc; font-size:14px; line-height:1.6; margin:0 0 28px; }
.modal-confirm-botones { display:flex; gap:12px; justify-content:center; }
.btn-confirm-cancelar { background:#3a3a3a; color:#ccc; border:1px solid #555; padding:10px 26px; border-radius:8px; cursor:pointer; font-size:14px; transition:all 0.2s; }
.btn-confirm-cancelar:hover { background:#444; color:#fff; }
.btn-confirm-eliminar { background:#dc3545; color:#fff; border:none; padding:10px 26px; border-radius:8px; cursor:pointer; font-size:14px; font-weight:600; transition:all 0.2s; }
.btn-confirm-eliminar:hover { background:#c82333; }
.btn-confirm-restaurar { background:#28a745; color:#fff; border:none; padding:10px 26px; border-radius:8px; cursor:pointer; font-size:14px; font-weight:600; transition:all 0.2s; }
.btn-confirm-restaurar:hover { background:#218838; }
.modal-mant-box { background:#2a2a2a; border:2px solid #ff7b00; border-radius:12px; padding:28px 32px; width:780px; max-width:96vw; max-height:92vh; overflow-y:auto; }
.modal-ver-box { background:#2a2a2a; border:2px solid #17a2b8; border-radius:12px; padding:28px 32px; width:700px; max-width:96vw; max-height:92vh; overflow-y:auto; }
.modal-mant-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.mant-seccion-titulo { color:#ccc; font-size:13px; font-weight:600; margin-bottom:10px; display:flex; align-items:center; gap:6px; }
.separador { height:1px; background:#3a3a3a; margin:16px 0; }
.form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:4px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-group label { color:#ccc; font-size:13px; font-weight:500; }
.form-group input, .form-group select, .form-group textarea { width:100%; padding:8px 12px; background:#3a3a3a; border:1px solid #555; border-radius:6px; color:#fff; font-size:14px; box-sizing:border-box; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline:none; border-color:#ff7b00; }
.form-group textarea { resize:vertical; }
.tarjeta-solicitud { background:#1e1e1e; border:1px solid #3a3a3a; border-radius:8px; padding:14px 16px; margin-top:10px; margin-bottom:4px; }
.tarjeta-fila { display:flex; flex-wrap:wrap; gap:16px; margin-bottom:8px; }
.tarjeta-fila:last-child { margin-bottom:0; }
.tarjeta-item { display:flex; flex-direction:column; gap:2px; min-width:110px; }
.tarjeta-label { color:#888; font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
.tarjeta-valor { color:#fff; font-size:14px; font-weight:600; }
.tabla-items-header { display:flex; align-items:center; gap:8px; padding:6px 10px; background:#1e1e1e; border-radius:6px 6px 0 0; font-size:11px; color:#888; text-transform:uppercase; letter-spacing:.5px; margin-bottom:2px; }
.item-fila { display:flex; align-items:center; gap:8px; padding:6px 4px; border-bottom:1px solid #2a2a2a; }
.item-fila-readonly { display:flex; align-items:center; gap:8px; padding:8px 10px; border-bottom:1px solid #2a2a2a; background:#1e1e1e; }
.item-select { flex:2; padding:7px 10px; background:#3a3a3a; border:1px solid #555; border-radius:6px; color:#fff; font-size:13px; }
.item-select:focus { outline:none; border-color:#ff7b00; }
.item-input { width:80px; padding:7px 8px; background:#3a3a3a; border:1px solid #555; border-radius:6px; color:#fff; font-size:13px; text-align:center; }
.item-input:focus { outline:none; border-color:#ff7b00; }
.item-nombre { flex:2; color:#ccc; font-size:13px; }
.item-cant-ro { width:80px; text-align:center; color:#fff; font-size:13px; font-weight:600; }
.item-precio { width:120px; padding:7px 10px; background:#2a2a2a; border:1px solid #3a3a3a; border-radius:6px; color:#aaa; font-size:13px; text-align:right; }
.item-subtotal { width:120px; padding:7px 10px; background:#1e1e1e; border:1px solid #3a3a3a; border-radius:6px; color:#4caf50; font-size:13px; font-weight:700; text-align:right; }
.btn-quitar { background:transparent; border:none; color:#dc3545; cursor:pointer; padding:4px; width:36px; transition:color 0.2s; }
.btn-quitar:hover { color:#ff4444; }
.btn-agregar-item { background:transparent; color:#ff7b00; border:1px dashed #ff7b00; padding:6px 14px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:5px; margin-top:6px; margin-bottom:4px; transition:all 0.2s; }
.btn-agregar-item:hover { background:rgba(255,123,0,0.1); }
.total-box { display:flex; align-items:center; justify-content:flex-end; gap:16px; background:#1e1e1e; border:1px solid #3a3a3a; border-radius:8px; padding:12px 20px; }
.total-label { color:#aaa; font-size:14px; }
.total-valor { color:#ff7b00; font-size:22px; font-weight:800; }
.modal-botones { display:flex; justify-content:flex-end; gap:12px; }
.btn-guardar-modal { background:#ff7b00; color:#fff; border:none; padding:10px 28px; border-radius:6px; cursor:pointer; font-weight:bold; font-size:14px; display:inline-flex; align-items:center; gap:6px; }
.btn-guardar-modal:hover { background:#e06a00; }
.btn-cancelar-modal { background:#555; color:#fff; border:none; padding:10px 28px; border-radius:6px; cursor:pointer; font-size:14px; }
.btn-cancelar-modal:hover { background:#444; }
.id-usuario { gap:10px; }
.icono-perfil { font-size:30px; color:#ff7b00; vertical-align:middle; }
.perfil-conductor { display:flex; align-items:center; gap:8px; }
.buscador { color:#000; }
.btn-reporte { background:transparent; color:#ff7b00; border:1px solid #ff7b00; padding:8px 18px; border-radius:6px; cursor:pointer; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; transition:all 0.2s; margin-left:10px; }
.btn-reporte:hover { background:#ff7b00; color:#fff; }
#tablaPapelera tbody tr { opacity:0.8; }
#tablaPapelera tbody tr:hover { opacity:1; }

.btn-facturar {
    display: inline-flex; align-items: center; gap: 6px;
    background: #1a3a26; color: #4caf50;
    border: 1px solid #2d6a3a;
    padding: 8px 18px; border-radius: 6px;
    text-decoration: none; font-size: 13px; font-weight: 600;
    transition: all 0.2s;
}
.btn-facturar:hover { background: #28a745; color: #fff; border-color: #28a745; }
 
.btn-ver-factura {
    display: inline-flex; align-items: center; gap: 6px;
    background: transparent; color: #17a2b8;
    border: 1px solid #17a2b8;
    padding: 8px 18px; border-radius: 6px;
    text-decoration: none; font-size: 13px; font-weight: 600;
    transition: all 0.2s;
}
.btn-ver-factura:hover { background: #17a2b8; color: #fff; }

</style>

<script>
const SERVICIOS = {!! json_encode($servicios->map(function($s) {
    return ['cod' => $s->cod_servicios, 'nom' => $s->nom_ser, 'pre' => (float)$s->pre_ser];
})->values()) !!};

const REPUESTOS = {!! json_encode($repuestos->map(function($r) {
    return ['cod' => $r->cod_repuestos, 'nom' => $r->nom_rep, 'pre' => (float)$r->pre_rep, 'stock' => $r->stock];
})->values()) !!};

const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── Pestañas ──
function cambiarTab(tab, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('seccion-activos').style.display  = tab === 'activos'  ? '' : 'none';
    document.getElementById('seccion-papelera').style.display = tab === 'papelera' ? '' : 'none';
}

// ── Modal Eliminar ──
let _idEliminar = null;
function confirmarEliminar(id, nombre) {
    _idEliminar = id;
    document.getElementById('confirmNombreCliente').textContent = nombre.trim() || 'este cliente';
    document.getElementById('modalConfirmarEliminar').style.display = 'flex';
}
function cerrarModalConfirmar() {
    _idEliminar = null;
    document.getElementById('modalConfirmarEliminar').style.display = 'none';
}
function ejecutarEliminar() {
    if (!_idEliminar) return;
    const f = document.getElementById('formEliminar');
    f.action = `/dashboard/mantenimientos/${_idEliminar}/eliminar`;
    f.style.display = 'block';
    f.submit();
}

// ── Modal Restaurar ──
let _idRestaurar = null;
function confirmarRestaurar(id, nombre) {
    _idRestaurar = id;
    document.getElementById('confirmNombreRestaurar').textContent = nombre.trim() || 'este cliente';
    document.getElementById('modalConfirmarRestaurar').style.display = 'flex';
}
function cerrarModalRestaurar() {
    _idRestaurar = null;
    document.getElementById('modalConfirmarRestaurar').style.display = 'none';
}
function ejecutarRestaurar() {
    if (!_idRestaurar) return;
    const f = document.getElementById('formRestaurar');
    f.action = `/dashboard/mantenimientos/${_idRestaurar}/restaurar`;
    f.style.display = 'block';
    f.submit();
}

// ── Cerrar modales al click fuera ──
window.addEventListener('click', e => {
    if (e.target === document.getElementById('modalConfirmarEliminar'))  cerrarModalConfirmar();
    if (e.target === document.getElementById('modalConfirmarRestaurar')) cerrarModalRestaurar();
    if (e.target === document.getElementById('modalMantenimiento'))      cerrarModalMantenimiento();
    if (e.target === document.getElementById('modalVerOrden'))           cerrarVerOrden();
    if (e.target === document.getElementById('modalEditarOrden'))        cerrarEditarOrden();
});

// ── Modal Nuevo Mantenimiento ──
function abrirModalMantenimiento() {
    const form = document.getElementById('formMantenimiento');
    form.reset();
    form.action = '{{ route('mantenimientos.guardar') }}';
    document.getElementById('methodMant').value = 'POST';
    document.getElementById('tituloModalMant').innerText = 'Nueva Orden de Trabajo';
    document.getElementById('selectSolicitud').disabled = false;
    document.getElementById('tarjetaSolicitud').style.display = 'none';
    document.querySelector('input[name="fec_ini_man"]').value = new Date().toISOString().slice(0,16);
    _contSrv = 0; _contRep = 0;
    document.getElementById('listaServicios').innerHTML = '';
    document.getElementById('listaRepuestos').innerHTML = '';
    agregarServicio();
    recalcularTotal('listaServicios', 'listaRepuestos', 'totalGeneral', 'totalHidden');
    document.getElementById('modalMantenimiento').style.display = 'flex';
}
function cerrarModalMantenimiento() {
    document.getElementById('modalMantenimiento').style.display = 'none';
}

function cargarDatosSolicitud(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) { document.getElementById('tarjetaSolicitud').style.display = 'none'; return; }
    document.getElementById('ts_cliente').textContent  = opt.dataset.cliente  || '—';
    document.getElementById('ts_telefono').textContent = opt.dataset.telefono || '—';
    document.getElementById('ts_vehiculo').textContent = opt.dataset.vehiculo  || '—';
    document.getElementById('ts_anio').textContent     = opt.dataset.anio     || '—';
    document.getElementById('ts_placa').textContent    = opt.dataset.placa    || '—';
    document.getElementById('ts_tipo').textContent     = opt.dataset.tipo     || '—';
    const srvRow = document.getElementById('ts_servicio_row');
    if (opt.dataset.servicio) {
        document.getElementById('ts_servicio').textContent = opt.dataset.servicio;
        srvRow.style.display = '';
    } else { srvRow.style.display = 'none'; }
    document.getElementById('tarjetaSolicitud').style.display = '';
}

// ── Modal Ver Orden ──
async function verOrden(cod) {
    document.getElementById('ver_cod').textContent = cod;
    document.getElementById('ver_loading').style.display   = 'block';
    document.getElementById('ver_contenido').style.display = 'none';
    document.getElementById('modalVerOrden').style.display = 'flex';

    try {
        const resp = await fetch(`/dashboard/mantenimientos/${cod}/orden`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        const data = await resp.json();

        document.getElementById('ver_tarjeta').innerHTML = `
            <div class="tarjeta-fila">
                <div class="tarjeta-item"><span class="tarjeta-label">Cliente</span><span class="tarjeta-valor">${data.cliente ?? '—'}</span></div>
                <div class="tarjeta-item"><span class="tarjeta-label">Teléfono</span><span class="tarjeta-valor">${data.telefono ?? '—'}</span></div>
                <div class="tarjeta-item"><span class="tarjeta-label">Vehículo</span><span class="tarjeta-valor">${data.vehiculo ?? '—'}</span></div>
                <div class="tarjeta-item"><span class="tarjeta-label">Placa</span><span class="tarjeta-valor">${data.placa ?? '—'}</span></div>
                <div class="tarjeta-item"><span class="tarjeta-label">Tipo</span><span class="tarjeta-valor">${data.tipo ?? '—'}</span></div>
            </div>`;

        const srvEl = document.getElementById('ver_servicios');
        srvEl.innerHTML = data.servicios.length
            ? data.servicios.map(s => `
                <div class="item-fila-readonly">
                    <span class="item-nombre">${s.nom}</span>
                    <span class="item-cant-ro">${s.cantidad}</span>
                    <span class="item-precio">Bs ${parseFloat(s.pre_uni).toFixed(2)}</span>
                    <span class="item-subtotal">Bs ${(s.cantidad * s.pre_uni).toFixed(2)}</span>
                </div>`).join('')
            : '<p style="color:#666;font-size:13px;padding:10px;">Sin servicios registrados.</p>';

        const repEl = document.getElementById('ver_repuestos');
        repEl.innerHTML = data.repuestos.length
            ? data.repuestos.map(r => `
                <div class="item-fila-readonly">
                    <span class="item-nombre">${r.nom}</span>
                    <span class="item-cant-ro">${r.cantidad}</span>
                    <span class="item-precio">Bs ${parseFloat(r.pre_uni).toFixed(2)}</span>
                    <span class="item-subtotal">Bs ${(r.cantidad * r.pre_uni).toFixed(2)}</span>
                </div>`).join('')
            : '<p style="color:#666;font-size:13px;padding:10px;">Sin repuestos registrados.</p>';

        document.getElementById('ver_total').textContent = 'Bs ' + parseFloat(data.total).toFixed(2);
        const descWrap = document.getElementById('ver_desc_wrap');
        if (data.descripcion) {
            document.getElementById('ver_desc').textContent = data.descripcion;
            descWrap.style.display = '';
        } else { descWrap.style.display = 'none'; }

        document.getElementById('ver_loading').style.display   = 'none';
        document.getElementById('ver_contenido').style.display = '';

        const accionesFac = document.getElementById('ver_acciones_fac');
        if (data.factura_emitida) {
            // Ya tiene factura → mostrar enlace para verla
            accionesFac.innerHTML = `
                <a href="/dashboard/facturas/${data.cod_factura}"
                class="btn-ver-factura"
                target="_blank">
                    <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">receipt_long</span>
                    Ver factura #${data.nro_factura}
                </a>`;
        } else {
            // Sin factura → botón para emitirla
            accionesFac.innerHTML = `
                <a href="/dashboard/mantenimientos/${cod}/facturar"
                class="btn-facturar">
                    <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">receipt_long</span>
                    Emitir factura
                </a>`;
        }

    } catch(e) {
        mostrarAviso('⚠️ Error al cargar la orden', 'error');
        cerrarVerOrden();
    }
}
function cerrarVerOrden() {
    document.getElementById('modalVerOrden').style.display = 'none';
}

// ── Modal Editar Orden (nuevo editar completo) ──
let _editCodMan  = null;
let _editContSrv = 0;
let _editContRep = 0;

async function abrirEditarOrden(cod) {
    _editCodMan  = cod;
    _editContSrv = 0;
    _editContRep = 0;
    document.getElementById('edit_cod').textContent        = cod;
    document.getElementById('edit_loading').style.display   = 'block';
    document.getElementById('edit_contenido').style.display = 'none';
    document.getElementById('modalEditarOrden').style.display = 'flex';
    document.getElementById('formEditarOrden').action =
        `/dashboard/mantenimientos/${cod}/orden/actualizar`;

    try {
        const resp = await fetch(`/dashboard/mantenimientos/${cod}/orden`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        const data = await resp.json();

        // Fechas
        document.getElementById('edit_fec_ini').value = data.fec_ini ? data.fec_ini.replace(' ','T') : '';
        document.getElementById('edit_fec_fin').value = data.fec_fin ? data.fec_fin.replace(' ','T') : '';

        // Descripción
        document.getElementById('edit_des_man').value = data.descripcion ?? '';

        // Servicios
        const listaSrv = document.getElementById('edit_listaServicios');
        listaSrv.innerHTML = '';
        data.servicios.forEach(s => {
            listaSrv.appendChild(crearFilaEdit('servicios', _editContSrv++, s.cod, s.cantidad, s.pre_uni));
        });
        if (!data.servicios.length) {
            listaSrv.appendChild(crearFilaEdit('servicios', _editContSrv++, '', 1, 0));
        }

        // Repuestos
        const listaRep = document.getElementById('edit_listaRepuestos');
        listaRep.innerHTML = '';
        data.repuestos.forEach(r => {
            listaRep.appendChild(crearFilaEdit('repuestos', _editContRep++, r.cod, r.cantidad, r.pre_uni));
        });

        recalcularTotal('edit_listaServicios', 'edit_listaRepuestos', 'edit_total', 'edit_totalHidden');

        document.getElementById('edit_loading').style.display   = 'none';
        document.getElementById('edit_contenido').style.display = '';

    } catch(e) {
        mostrarAviso('⚠️ Error al cargar la orden', 'error');
        cerrarEditarOrden();
    }
}
function cerrarEditarOrden() {
    document.getElementById('modalEditarOrden').style.display = 'none';
}
function editAgregarServicio() {
    document.getElementById('edit_listaServicios').appendChild(crearFilaEdit('servicios', _editContSrv++, '', 1, 0));
    recalcularTotal('edit_listaServicios','edit_listaRepuestos','edit_total','edit_totalHidden');
}
function editAgregarRepuesto() {
    document.getElementById('edit_listaRepuestos').appendChild(crearFilaEdit('repuestos', _editContRep++, '', 1, 0));
    recalcularTotal('edit_listaServicios','edit_listaRepuestos','edit_total','edit_totalHidden');
}

// ── Helpers filas ──
let _contSrv = 0;
let _contRep = 0;

function opcionesServicios(selVal) {
    return SERVICIOS.map(s =>
        `<option value="${s.cod}" data-precio="${s.pre}" ${s.cod === selVal ? 'selected' : ''}>
            ${s.nom} — Bs ${s.pre.toFixed(2)}
        </option>`
    ).join('');
}
function opcionesRepuestos(selVal) {
    return REPUESTOS.map(r =>
        `<option value="${r.cod}" data-precio="${r.pre}" data-stock="${r.stock}" ${r.cod === selVal ? 'selected' : ''}>
            ${r.nom} (Stock: ${r.stock}) — Bs ${r.pre.toFixed(2)}
        </option>`
    ).join('');
}

// Fila para modal NUEVO
function crearFilaNuevo(tipo, idx) {
    return _crearFila(tipo, idx, '', 1, 0,
        tipo === 'servicios' ? 'listaServicios' : 'listaRepuestos');
}
// Fila para modal EDITAR
function crearFilaEdit(tipo, idx, codSel, cant, pre) {
    return _crearFila(tipo, idx, codSel, cant, pre,
        tipo === 'servicios' ? 'edit_listaServicios' : 'edit_listaRepuestos');
}

function _crearFila(tipo, idx, codSel, cant, pre, listaId) {
    const div  = document.createElement('div');
    div.className = 'item-fila';
    const opts = tipo === 'servicios' ? opcionesServicios(codSel) : opcionesRepuestos(codSel);
    const req  = tipo === 'servicios' ? 'required' : '';
    const preF = parseFloat(pre).toFixed(2);
    const sub  = (cant * parseFloat(pre)).toFixed(2);
    const isEdit = listaId.startsWith('edit_');
    const srvId  = isEdit ? 'edit_listaServicios' : 'listaServicios';
    const repId  = isEdit ? 'edit_listaRepuestos' : 'listaRepuestos';
    const totId  = isEdit ? 'edit_total'          : 'totalGeneral';
    const hidId  = isEdit ? 'edit_totalHidden'    : 'totalHidden';

    div.innerHTML = `
        <select name="${tipo}[${idx}][cod]" class="item-select" ${req}
                onchange="onCambioItem(this, '${listaId}')">
            <option value="">Seleccione...</option>
            ${opts}
        </select>
        <input name="${tipo}[${idx}][cantidad]" type="number" min="1" value="${cant}"
               class="item-input"
               oninput="recalcularTotal('${srvId}','${repId}','${totId}','${hidId}')">
        <input name="${tipo}[${idx}][pre_uni]" type="hidden" class="item-precio-hidden" value="${preF}">
        <div class="item-precio" data-precio="${preF}">Bs ${preF}</div>
        <div class="item-subtotal">Bs ${sub}</div>
        <button type="button" class="btn-quitar"
                onclick="quitarFila(this, '${listaId}', '${tipo}')">
            <span class="material-symbols-outlined" style="font-size:18px;">remove_circle</span>
        </button>`;
    return div;
}

function onCambioItem(sel, listaId) {
    const opt    = sel.options[sel.selectedIndex];
    const precio = parseFloat(opt.dataset.precio) || 0;
    const fila   = sel.closest('.item-fila');
    fila.querySelector('.item-precio').textContent    = 'Bs ' + precio.toFixed(2);
    fila.querySelector('.item-precio').dataset.precio = precio;
    fila.querySelector('.item-precio-hidden').value   = precio;
    const isEdit = listaId.startsWith('edit_');
    recalcularTotal(
        isEdit ? 'edit_listaServicios' : 'listaServicios',
        isEdit ? 'edit_listaRepuestos' : 'listaRepuestos',
        isEdit ? 'edit_total'          : 'totalGeneral',
        isEdit ? 'edit_totalHidden'    : 'totalHidden'
    );
}

function agregarServicio() {
    document.getElementById('listaServicios').appendChild(crearFilaNuevo('servicios', _contSrv++));
    recalcularTotal('listaServicios','listaRepuestos','totalGeneral','totalHidden');
}
function agregarRepuesto() {
    document.getElementById('listaRepuestos').appendChild(crearFilaNuevo('repuestos', _contRep++));
    recalcularTotal('listaServicios','listaRepuestos','totalGeneral','totalHidden');
}

function quitarFila(btn, listaId, tipo) {
    const lista = document.getElementById(listaId);
    if (tipo === 'servicios' && lista.children.length <= 1) {
        mostrarAviso('⚠️ Debe haber al menos un servicio', 'error');
        return;
    }
    btn.closest('.item-fila').remove();
    const isEdit = listaId.startsWith('edit_');
    recalcularTotal(
        isEdit ? 'edit_listaServicios' : 'listaServicios',
        isEdit ? 'edit_listaRepuestos' : 'listaRepuestos',
        isEdit ? 'edit_total'          : 'totalGeneral',
        isEdit ? 'edit_totalHidden'    : 'totalHidden'
    );
}

function recalcularTotal(listaSrvId, listaRepId, totalId, hiddenId) {
    let total = 0;
    [listaSrvId, listaRepId].forEach(id => {
        document.querySelectorAll(`#${id} .item-fila`).forEach(fila => {
            const cant = parseFloat(fila.querySelector('.item-input')?.value) || 0;
            const pre  = parseFloat(fila.querySelector('.item-precio')?.dataset.precio) || 0;
            const sub  = cant * pre;
            const subEl = fila.querySelector('.item-subtotal');
            if (subEl) subEl.textContent = 'Bs ' + sub.toFixed(2);
            total += sub;
        });
    });
    document.getElementById(totalId).textContent = 'Bs ' + total.toFixed(2);
    document.getElementById(hiddenId).value = total.toFixed(2);
}

// ── Filtros + Buscador ──
document.querySelectorAll('.filtro-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        filtrar();
    });
});
document.getElementById('searchInput').addEventListener('input', filtrar);
function filtrar() {
    const term = document.getElementById('searchInput').value.toLowerCase().trim();
    document.querySelectorAll('#tablaMantenimientos tbody tr').forEach(fila => {
        fila.style.display = (term === '' || fila.textContent.toLowerCase().includes(term)) ? '' : 'none';
    });
}

// ── Toast ──
function mostrarAviso(msg, tipo) {
    const a = document.createElement('div');
    a.textContent = msg;
    Object.assign(a.style, {
        position:'fixed', bottom:'20px', right:'20px', padding:'12px 18px',
        borderRadius:'8px', fontWeight:'bold', zIndex:9999, transition:'opacity 0.5s ease',
        color: tipo === 'success' ? '#fff' : '#000',
        backgroundColor: tipo === 'success' ? '#00c851' : '#ffbb33',
        boxShadow:'0 0 10px rgba(0,0,0,0.3)'
    });
    document.body.appendChild(a);
    setTimeout(() => a.style.opacity = '0', 2000);
    setTimeout(() => a.remove(), 2500);
}

// ── Limpiar repuestos vacíos antes de enviar ──
document.getElementById('formMantenimiento').addEventListener('submit', function() {
    document.querySelectorAll('#listaRepuestos .item-fila').forEach(function(fila) {
        const sel = fila.querySelector('select[name*="[cod]"]');
        if (!sel || !sel.value) fila.parentNode.removeChild(fila);
    });
    document.querySelectorAll('#listaRepuestos .item-fila').forEach(function(fila, idx) {
        fila.querySelectorAll('[name]').forEach(function(el) {
            el.name = el.name.replace(/repuestos\[\d+\]/, 'repuestos[' + idx + ']');
        });
    });
});
</script>

@endsection