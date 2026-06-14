@extends('layouts.dashboard')

@section('contenido')

@if ($errors->any())
    <div style="background:#dc3545;color:#fff;padding:14px 20px;border-radius:8px;margin-bottom:16px;">
        <strong>Errores:</strong>
        <ul style="margin:8px 0 0 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('success'))
    <div style="background:#28a745;color:#fff;padding:14px 20px;border-radius:8px;margin-bottom:16px;">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div style="background:#dc3545;color:#fff;padding:14px 20px;border-radius:8px;margin-bottom:16px;">
        {{ session('error') }}
    </div>
@endif

<div class="Dmant-content">

    <div class="header-content">
        <h2>
            Seguimientos
            <a href="{{ route('seguimientos.reporte') }}" class="btn-reporte">
                <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">summarize</span>
                Reporte
            </a>
        </h2>
    </div>

    {{-- ── TABS ── --}}
    <div class="tabs-seccion">
        <button class="tab-btn active" onclick="cambiarTab('activos', this)">
            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">sticky_note_2</span>
            Seguimientos activos
            <span class="tab-count">{{ $seguimientos->total() }}</span>
        </button>
        <button class="tab-btn" onclick="cambiarTab('papelera', this)">
            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">delete</span>
            Papelera
            <span class="tab-count {{ $eliminados->count() > 0 ? 'has-items' : '' }}">
                {{ $eliminados->count() }}
            </span>
        </button>
    </div>

    {{-- ── SECCIÓN ACTIVOS ── --}}
    <div id="seccion-activos">

        {{-- Filtros por tipo de mantenimiento --}}
        <div class="filtros-estado">
            <button class="filtro-btn active" data-tipo="TODOS">Todos</button>
            <button class="filtro-btn" data-tipo="Preventivo">Preventivo</button>
            <button class="filtro-btn" data-tipo="Correctivo">Correctivo</button>
        </div>

        {{-- Buscador --}}
        <div class="search-container">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Buscar por técnico, placa, observación...">
                <div class="search-icon">
                    <span class="material-symbols-outlined buscador">search</span>
                </div>
            </div>
        </div>

        <div class="table-wrapper">
            <table id="tablaSeguimientos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Técnico</th>
                        <th>Placa</th>
                        <th>Tipo</th>
                        <th>Observación</th>
                        <th>Fecha</th>
                        <th>Repuestos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($seguimientos as $seg)
                        @php
                            $solicitud = $seg->solicitud;
                            $tecnico   = $seg->tecnico;
                            $vehiculo  = $solicitud?->vehiculo;
                            $tipo      = $solicitud?->tma_sol ?? '—';
                            $esTipo    = strtolower($tipo);
                        @endphp
                        <tr data-tipo="{{ $tipo }}">
                            <td class="id-usuario">
                                <span class="material-symbols-outlined icono-perfil">sticky_note_2</span>
                                {{ $seg->cod_seg }}
                            </td>
                            <td>
                                @if($tecnico)
                                    <div class="perfil-conductor">
                                        <span class="material-symbols-outlined icono-perfil">engineering</span>
                                        <span>{{ $tecnico->nom_usu ?? $tecnico->nombre ?? '—' }}</span>
                                    </div>
                                @else
                                    <span style="color:#777;">Sin técnico</span>
                                @endif
                            </td>
                            <td>
                                @if($vehiculo)
                                    <span class="badge-placa">{{ $vehiculo->pla_veh ?? $vehiculo->cod_vehiculos ?? '—' }}</span>
                                @else
                                    <span style="color:#777;">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge-tipo {{ str_contains($esTipo, 'correctivo') ? 'correctivo' : 'preventivo' }}">
                                    {{ $tipo }}
                                </span>
                            </td>
                            <td class="obs-col" title="{{ $seg->obs_seg }}">
                                {{ Str::limit($seg->obs_seg, 60) ?? '—' }}
                            </td>
                            <td>
                                {{ $seg->fcs_seg ? \Carbon\Carbon::parse($seg->fcs_seg)->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td style="text-align:center;">
                                @php $totalRep = $seg->repuestosUsados->count(); @endphp
                                @if($totalRep > 0)
                                    <span class="badge-rep" title="Ver repuestos">{{ $totalRep }}</span>
                                @else
                                    <span style="color:#777;font-size:13px;">—</span>
                                @endif
                            </td>
                            <td class="acciones">
                                <button type="button" class="btn-ver-seg"
                                        onclick="verSeguimiento('{{ $seg->cod_seg }}')">
                                    <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">visibility</span>
                                    Ver
                                </button>
                                <button type="button" class="btn-editar-seg"
                                        onclick="abrirEditarSeguimiento('{{ $seg->cod_seg }}')">
                                    <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">edit_note</span>
                                    Editar
                                </button>
                                <button type="button" class="btn-eliminar-seg"
                                        onclick="confirmarEliminar(
                                            '{{ $seg->cod_seg }}',
                                            '{{ \Carbon\Carbon::parse($seg->fcs_seg)->format('d/m/Y') }}'
                                        )">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;color:#aaa;padding:30px;">
                                No hay seguimientos registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if ($seguimientos->hasPages())
        <div class="pagination-container">
            @if ($seguimientos->onFirstPage())
                <span class="page-btn disabled">&#10094;</span>
            @else
                <a href="{{ $seguimientos->previousPageUrl() }}" class="page-btn">&#10094;</a>
            @endif
            @if ($seguimientos->currentPage() > 3)
                <a href="{{ $seguimientos->url(1) }}" class="page-btn">1</a>
                <span class="page-dots">...</span>
            @endif
            @for ($i = max(1, $seguimientos->currentPage()-2);
                  $i <= min($seguimientos->lastPage(), $seguimientos->currentPage()+2); $i++)
                @if ($i == $seguimientos->currentPage())
                    <span class="page-btn active">{{ $i }}</span>
                @else
                    <a href="{{ $seguimientos->url($i) }}" class="page-btn">{{ $i }}</a>
                @endif
            @endfor
            @if ($seguimientos->currentPage() < $seguimientos->lastPage() - 2)
                <span class="page-dots">...</span>
                <a href="{{ $seguimientos->url($seguimientos->lastPage()) }}" class="page-btn">{{ $seguimientos->lastPage() }}</a>
            @endif
            @if ($seguimientos->hasMorePages())
                <a href="{{ $seguimientos->nextPageUrl() }}" class="page-btn">&#10095;</a>
            @else
                <span class="page-btn disabled">&#10095;</span>
            @endif
        </div>
        @endif

    </div>{{-- /seccion-activos --}}

    {{-- ── SECCIÓN PAPELERA ── --}}
    <div id="seccion-papelera" style="display:none;">
        <div class="papelera-header">
            <span class="material-symbols-outlined" style="color:#dc3545;font-size:22px;">delete_sweep</span>
            <span>Seguimientos eliminados — pueden restaurarse en cualquier momento.</span>
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
                            <th>ID</th>
                            <th>Técnico</th>
                            <th>Placa</th>
                            <th>Tipo</th>
                            <th>Observación</th>
                            <th>Eliminado el</th>
                            <th>Restaurado el</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($eliminados as $e)
                            @php
                                $sol_e = $e->solicitud;
                                $tec_e = $e->tecnico;
                                $veh_e = $sol_e?->vehiculo;
                                $tip_e = $sol_e?->tma_sol ?? '—';
                            @endphp
                            <tr>
                                <td class="id-usuario">
                                    <span class="material-symbols-outlined icono-perfil" style="color:#666;">sticky_note_2</span>
                                    {{ $e->cod_seg }}
                                </td>
                                <td>{{ $tec_e?->nom_usu ?? $tec_e?->nombre ?? '—' }}</td>
                                <td>
                                    <span class="badge-placa">{{ $veh_e?->pla_veh ?? $veh_e?->cod_vehiculos ?? '—' }}</span>
                                </td>
                                <td>
                                    <span class="badge-tipo {{ str_contains(strtolower($tip_e), 'correctivo') ? 'correctivo' : 'preventivo' }}">
                                        {{ $tip_e }}
                                    </span>
                                </td>
                                <td class="obs-col">{{ Str::limit($e->obs_seg, 50) ?? '—' }}</td>
                                <td>
                                    <span style="color:#e57373;font-size:12px;">
                                        {{ \Carbon\Carbon::parse($e->deleted_at)->format('d/m/Y H:i') }}
                                    </span>
                                </td>
                                <td>
                                    @if($e->restored_at)
                                        <span style="color:#4caf50;font-size:12px;">
                                            {{ \Carbon\Carbon::parse($e->restored_at)->format('d/m/Y H:i') }}
                                        </span>
                                    @else
                                        <span style="color:#555;font-size:12px;">—</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn-restaurar"
                                            onclick="confirmarRestaurar(
                                                '{{ $e->cod_seg }}',
                                                '{{ \Carbon\Carbon::parse($e->fcs_seg)->format('d/m/Y') }}'
                                            )">
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
    </div>{{-- /seccion-papelera --}}

</div>{{-- /Dmant-content --}}


{{-- ══════════════════════════════════════════
     MODAL VER SEGUIMIENTO
════════════════════════════════════════════ --}}
<div id="modalVerSeg" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:2000;justify-content:center;align-items:center;">
    <div class="modal-seg-box modal-ver-box">
        <div class="modal-seg-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="material-symbols-outlined" style="color:#ff7b00;font-size:24px;">visibility</span>
                <h2 style="margin:0;color:#ff7b00;font-size:18px;font-weight:700;">
                    Detalle — <span id="ver_cod_seg">—</span>
                </h2>
            </div>
            <span onclick="cerrarModal('modalVerSeg')" style="font-size:26px;cursor:pointer;color:#ccc;">&times;</span>
        </div>

        <div id="ver_seg_loading" style="text-align:center;padding:40px;color:#aaa;">
            <span class="material-symbols-outlined" style="font-size:40px;display:block;margin-bottom:10px;animation:spin 1s linear infinite;">refresh</span>
            Cargando...
        </div>

        <div id="ver_seg_contenido" style="display:none;">

            {{-- Tarjeta info --}}
            <div class="tarjeta-seg" id="ver_seg_tarjeta"></div>

            <div class="seg-separador"></div>

            {{-- Observación --}}
            <div class="seg-seccion-titulo">
                <span class="material-symbols-outlined" style="color:#ff7b00;font-size:18px;">notes</span>
                Observación
            </div>
            <div id="ver_seg_obs" class="seg-obs-block"></div>

            <div class="seg-separador"></div>

            {{-- Repuestos --}}
            <div class="seg-seccion-titulo">
                <span class="material-symbols-outlined" style="color:#ff7b00;font-size:18px;">build</span>
                Repuestos utilizados
            </div>
            <div id="ver_seg_repuestos"></div>

        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:20px;">
            <button class="btn-cancelar-modal" onclick="cerrarModal('modalVerSeg')">Cerrar</button>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════
     MODAL EDITAR SEGUIMIENTO
════════════════════════════════════════════ --}}
<div id="modalEditarSeg" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:2000;justify-content:center;align-items:center;">
    <div class="modal-seg-box">
        <div class="modal-seg-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="material-symbols-outlined" style="color:#ff7b00;font-size:24px;">edit_note</span>
                <h2 style="margin:0;color:#ff7b00;font-size:18px;font-weight:700;">
                    Editar — <span id="edit_cod_seg">—</span>
                </h2>
            </div>
            <span onclick="cerrarModal('modalEditarSeg')" style="font-size:26px;cursor:pointer;color:#ccc;">&times;</span>
        </div>

        <div id="edit_seg_loading" style="text-align:center;padding:40px;color:#aaa;">
            <span class="material-symbols-outlined" style="font-size:40px;display:block;margin-bottom:10px;animation:spin 1s linear infinite;">refresh</span>
            Cargando...
        </div>

        <div id="edit_seg_contenido" style="display:none;">

            {{-- Info de contexto (solo lectura) --}}
            <div class="tarjeta-seg" id="edit_seg_tarjeta" style="margin-bottom:16px;"></div>

            {{-- Observaciones --}}
            <div class="seg-seccion-titulo">
                <span class="material-symbols-outlined" style="color:#ff7b00;font-size:18px;">notes</span>
                Observación
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <textarea id="edit_obs_seg" name="obs_seg" rows="3"
                          style="width:100%;padding:10px 12px;background:#3a3a3a;border:1px solid #555;
                                 border-radius:6px;color:#fff;font-size:14px;resize:vertical;box-sizing:border-box;"
                          placeholder="Observaciones del seguimiento..."></textarea>
            </div>

            <div class="seg-separador"></div>

            {{-- Lista de repuestos actuales --}}
            <div class="seg-seccion-titulo">
                <span class="material-symbols-outlined" style="color:#ff7b00;font-size:18px;">build</span>
                Repuestos en este seguimiento
            </div>
            <div id="edit_lista_repuestos" style="max-height:220px;overflow-y:auto;margin-bottom:12px;"></div>

            {{-- Agregar nuevo repuesto --}}
            <div class="edit-agregar-wrap">
                <select id="edit_select_rep" class="edit-select-rep">
                    <option value="">— Seleccionar repuesto —</option>
                </select>
                <input type="number" id="edit_qty_rep" min="1" value="1" class="edit-qty-input" placeholder="Qty">
                <button class="edit-btn-agregar" onclick="editSegAgregarRepuesto()">
                    <span class="material-symbols-outlined" style="font-size:16px;">add</span>
                    Agregar
                </button>
            </div>

            <p id="edit_seg_error" style="color:#ef4444;font-size:13px;margin-top:8px;display:none;"></p>

            <div class="modal-botones" style="margin-top:20px;">
                <button class="btn-guardar-modal" onclick="guardarEdicionSeg()">
                    <span class="material-symbols-outlined" style="font-size:15px;vertical-align:middle;">check_circle</span>
                    Guardar cambios
                </button>
                <button class="btn-cancelar-modal" onclick="cerrarModal('modalEditarSeg')">Cancelar</button>
            </div>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════
     MODAL CONFIRMAR ELIMINAR
════════════════════════════════════════════ --}}
<div id="modalConfirmarEliminar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:3000;justify-content:center;align-items:center;">
    <div class="modal-confirm-box">
        <div class="modal-confirm-icon">
            <span class="material-symbols-outlined">delete_forever</span>
        </div>
        <h3 class="modal-confirm-titulo">¿Eliminar seguimiento?</h3>
        <p class="modal-confirm-texto">
            El seguimiento del <strong id="confirmFechaSeg">—</strong>
            se moverá a la papelera.<br>
            <span style="color:#aaa;font-size:13px;">Podrás restaurarlo desde la pestaña "Papelera".</span>
        </p>
        <div class="modal-confirm-botones">
            <button class="btn-confirm-cancelar" onclick="cerrarModal('modalConfirmarEliminar')">Cancelar</button>
            <button class="btn-confirm-eliminar" onclick="ejecutarEliminar()">Sí, eliminar</button>
        </div>
        <form id="formEliminar" method="POST" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>


{{-- ══════════════════════════════════════════
     MODAL CONFIRMAR RESTAURAR
════════════════════════════════════════════ --}}
<div id="modalConfirmarRestaurar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:3000;justify-content:center;align-items:center;">
    <div class="modal-confirm-box">
        <div class="modal-confirm-icon restaurar">
            <span class="material-symbols-outlined">restore</span>
        </div>
        <h3 class="modal-confirm-titulo">¿Restaurar seguimiento?</h3>
        <p class="modal-confirm-texto">
            El seguimiento del <strong id="confirmFechaRestaurar">—</strong>
            volverá a aparecer activo.
        </p>
        <div class="modal-confirm-botones">
            <button class="btn-confirm-cancelar" onclick="cerrarModal('modalConfirmarRestaurar')">Cancelar</button>
            <button class="btn-confirm-restaurar" onclick="ejecutarRestaurar()">Sí, restaurar</button>
        </div>
        <form id="formRestaurar" method="POST" style="display:none;">@csrf</form>
    </div>
</div>


{{-- ══════════════════════════════════════════
     ESTILOS ESPECÍFICOS DE ESTA VISTA
════════════════════════════════════════════ --}}
<style>
@keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }

/* ── Tabs ── */
.tabs-seccion { display:flex; gap:4px; margin-bottom:20px; border-bottom:2px solid #3a3a3a; }
.tab-btn { display:flex; align-items:center; gap:7px; padding:9px 20px; background:transparent;
    border:none; border-bottom:3px solid transparent; color:#aaa; cursor:pointer;
    font-size:14px; font-weight:500; margin-bottom:-2px; transition:all 0.2s; }
.tab-btn:hover { color:#fff; }
.tab-btn.active { color:#ff7b00; border-bottom-color:#ff7b00; }
.tab-count { background:#3a3a3a; color:#ccc; padding:1px 7px; border-radius:10px; font-size:11px; font-weight:600; }
.tab-count.has-items { background:#dc3545; color:#fff; }

/* ── Filtros ── */
.filtros-estado { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
.filtro-btn { padding:6px 18px; border-radius:20px; border:1px solid #555; background:#2a2a2a;
    color:#ccc; cursor:pointer; font-size:13px; transition:all 0.2s; }
.filtro-btn:hover { border-color:#ff7b00; color:#ff7b00; }
.filtro-btn.active { background:#ff7b00; color:#fff; border-color:#ff7b00; font-weight:bold; }

/* ── Badges ── */
.badge-tipo { padding:3px 10px; border-radius:8px; font-size:12px; font-weight:600;
    background:transparent; white-space:nowrap; }
.badge-tipo.preventivo { border:1px solid #3a8fd4; color:#3a8fd4; }
.badge-tipo.correctivo { border:1px solid #9b59b6; color:#9b59b6; }

.badge-placa { background:#1e1e1e; border:1px solid #ff7b00; color:#ff7b00;
    padding:3px 10px; border-radius:6px; font-size:12px; font-weight:700; letter-spacing:1px; }

.badge-rep { background:rgba(255,123,0,0.15); border:1px solid #ff7b00; color:#ff7b00;
    padding:2px 10px; border-radius:12px; font-size:12px; font-weight:700; }

/* ── Observación columna ── */
.obs-col { max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    color:#aaa; font-size:13px; }

/* ── Botones de acción en tabla ── */
.btn-ver-seg { background:transparent; color:#17a2b8; border:1px solid #17a2b8;
    padding:5px 10px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;
    display:inline-flex; align-items:center; gap:4px; transition:all 0.2s; }
.btn-ver-seg:hover { background:#17a2b8; color:#fff; }

.btn-editar-seg { background:transparent; color:#9c6fe4; border:1px solid #9c6fe4;
    padding:5px 10px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;
    display:inline-flex; align-items:center; gap:4px; transition:all 0.2s; }
.btn-editar-seg:hover { background:#9c6fe4; color:#fff; }

.btn-eliminar-seg { background:transparent; color:#E24B4A; border:1px solid #E24B4A;
    padding:5px 10px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;
    display:inline-flex; align-items:center; gap:4px; transition:all 0.2s; }
.btn-eliminar-seg:hover { background:#E24B4A; color:#fff; }

.btn-restaurar { background:#1a3a26; color:#4caf50; border:1px solid #2d6a3a;
    padding:5px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;
    display:inline-flex; align-items:center; gap:4px; transition:all 0.2s; }
.btn-restaurar:hover { background:#28a745; color:#fff; border-color:#28a745; }

/* ── Papelera header ── */
.papelera-header { display:flex; align-items:center; gap:10px; background:rgba(220,53,69,0.08);
    border:1px solid rgba(220,53,69,0.25); border-radius:8px; padding:10px 16px;
    margin-bottom:16px; color:#f08090; font-size:13px; }

/* ── Modales ── */
.modal-seg-box { background:#2a2a2a; border:2px solid #ff7b00; border-radius:12px;
    padding:28px 32px; width:680px; max-width:96vw; max-height:90vh; overflow-y:auto; }
.modal-ver-box { border-color:#17a2b8; }

.modal-seg-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }

/* Tarjeta info en modal */
.tarjeta-seg { background:#1e1e1e; border:1px solid #3a3a3a; border-radius:8px;
    padding:14px 16px; display:flex; flex-wrap:wrap; gap:16px; }
.tarjeta-item { display:flex; flex-direction:column; gap:2px; min-width:110px; }
.tarjeta-label { color:#888; font-size:11px; text-transform:uppercase; letter-spacing:.5px; }
.tarjeta-valor { color:#fff; font-size:14px; font-weight:600; }

/* Separador y secciones */
.seg-separador { height:1px; background:#3a3a3a; margin:16px 0; }
.seg-seccion-titulo { color:#ccc; font-size:13px; font-weight:600; margin-bottom:10px;
    display:flex; align-items:center; gap:6px; }

/* Bloque observación */
.seg-obs-block { background:#1e1e1e; border:1px solid #3a3a3a; border-radius:8px;
    padding:14px 16px; color:#ccc; font-size:14px; line-height:1.6; min-height:50px; }

/* Fila repuesto en modal ver */
.seg-rep-fila { display:flex; align-items:center; gap:12px; padding:10px 14px;
    background:#1e1e1e; border-radius:8px; margin-bottom:8px; border:1px solid #2a2a2a; }
.seg-rep-nombre { flex:1; color:#ccc; font-size:13px; }
.seg-rep-qty { background:rgba(255,123,0,0.15); border:1px solid #ff7b00; color:#ff7b00;
    padding:2px 10px; border-radius:10px; font-size:12px; font-weight:700; }

/* Fila repuesto en modal editar */
.edit-rep-fila { display:flex; align-items:center; gap:10px; padding:10px 12px;
    background:#1e1e1e; border-radius:8px; margin-bottom:8px; border:1px solid #2a2a2a; }
.edit-rep-nombre { flex:1; color:#ccc; font-size:13px; }
.edit-rep-stock  { color:#888; font-size:11px; white-space:nowrap; }
.edit-rep-qty    { width:70px; padding:6px 8px; background:#3a3a3a; border:1px solid #555;
    border-radius:6px; color:#fff; font-size:13px; text-align:center; }
.edit-rep-qty:focus { outline:none; border-color:#ff7b00; }
.edit-btn-quitar { background:none; border:none; color:#ef4444; cursor:pointer;
    padding:4px; display:flex; align-items:center; transition:color .2s; }
.edit-btn-quitar:hover { color:#ff6b6b; }

/* Agregar repuesto en editar */
.edit-agregar-wrap { display:flex; gap:8px; align-items:center; margin-top:12px; flex-wrap:wrap; }
.edit-select-rep { flex:1; min-width:180px; padding:8px 10px; background:#3a3a3a;
    border:1px solid #555; border-radius:6px; color:#fff; font-size:13px; }
.edit-select-rep:focus { outline:none; border-color:#ff7b00; }
.edit-qty-input { width:70px; padding:8px; background:#3a3a3a; border:1px solid #555;
    border-radius:6px; color:#fff; font-size:13px; text-align:center; }
.edit-qty-input:focus { outline:none; border-color:#ff7b00; }
.edit-btn-agregar { background:linear-gradient(45deg,#ff7b00,#e65c00); color:#fff; border:none;
    padding:8px 16px; border-radius:6px; cursor:pointer; font-weight:700; font-size:13px;
    display:inline-flex; align-items:center; gap:6px; transition:.2s; }
.edit-btn-agregar:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(255,120,0,.4); }

/* Confirm boxes */
.modal-confirm-box { background:#2a2a2a; border:2px solid #444; border-radius:12px;
    padding:36px 40px; width:420px; max-width:94vw; text-align:center; }
.modal-confirm-icon { width:64px; height:64px; border-radius:50%; background:rgba(220,53,69,0.12);
    border:2px solid rgba(220,53,69,0.35); display:flex; align-items:center; justify-content:center;
    margin:0 auto 18px; }
.modal-confirm-icon .material-symbols-outlined { font-size:32px; color:#dc3545; }
.modal-confirm-icon.restaurar { background:rgba(40,167,69,0.12); border-color:rgba(40,167,69,0.35); }
.modal-confirm-icon.restaurar .material-symbols-outlined { color:#28a745; }
.modal-confirm-titulo { color:#fff; font-size:18px; margin:0 0 12px; font-weight:600; }
.modal-confirm-texto  { color:#ccc; font-size:14px; line-height:1.6; margin:0 0 28px; }
.modal-confirm-botones { display:flex; gap:12px; justify-content:center; }
.btn-confirm-cancelar { background:#3a3a3a; color:#ccc; border:1px solid #555;
    padding:10px 26px; border-radius:8px; cursor:pointer; font-size:14px; transition:all .2s; }
.btn-confirm-cancelar:hover { background:#444; color:#fff; }
.btn-confirm-eliminar { background:#dc3545; color:#fff; border:none; padding:10px 26px;
    border-radius:8px; cursor:pointer; font-size:14px; font-weight:600; transition:all .2s; }
.btn-confirm-eliminar:hover { background:#c82333; }
.btn-confirm-restaurar { background:#28a745; color:#fff; border:none; padding:10px 26px;
    border-radius:8px; cursor:pointer; font-size:14px; font-weight:600; transition:all .2s; }
.btn-confirm-restaurar:hover { background:#218838; }

/* Botones del modal */
.modal-botones { display:flex; justify-content:flex-end; gap:12px; }
.btn-guardar-modal { background:#ff7b00; color:#fff; border:none; padding:10px 28px;
    border-radius:6px; cursor:pointer; font-weight:bold; font-size:14px;
    display:inline-flex; align-items:center; gap:6px; }
.btn-guardar-modal:hover { background:#e06a00; }
.btn-cancelar-modal { background:#555; color:#fff; border:none; padding:10px 28px;
    border-radius:6px; cursor:pointer; font-size:14px; }
.btn-cancelar-modal:hover { background:#444; }
</style>


{{-- ══════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════════ --}}
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ── Estado edición ────────────────────────────────────────
let editSegState = {
    cod_seg:     null,
    repuestos:   [],
    eliminados:  [],
    disponibles: [],
};

// ── Tabs ─────────────────────────────────────────────────
function cambiarTab(tab, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('seccion-activos').style.display  = tab === 'activos'  ? '' : 'none';
    document.getElementById('seccion-papelera').style.display = tab === 'papelera' ? '' : 'none';
}

// ── Filtros + Buscador ────────────────────────────────────
document.querySelectorAll('.filtro-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        filtrar();
    });
});
document.getElementById('searchInput').addEventListener('input', filtrar);

function filtrar() {
    const term  = document.getElementById('searchInput').value.toLowerCase().trim();
    const tipo  = document.querySelector('.filtro-btn.active')?.dataset.tipo ?? 'TODOS';

    document.querySelectorAll('#tablaSeguimientos tbody tr').forEach(fila => {
        const matchTerm = term === '' || fila.textContent.toLowerCase().includes(term);
        const matchTipo = tipo === 'TODOS' || (fila.dataset.tipo ?? '').toLowerCase().includes(tipo.toLowerCase());
        fila.style.display = (matchTerm && matchTipo) ? '' : 'none';
    });
}

// ── Helpers modal ─────────────────────────────────────────
function cerrarModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

window.addEventListener('click', e => {
    ['modalVerSeg','modalEditarSeg','modalConfirmarEliminar','modalConfirmarRestaurar'].forEach(id => {
        const el = document.getElementById(id);
        if (el && e.target === el) cerrarModal(id);
    });
});

document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    ['modalVerSeg','modalEditarSeg','modalConfirmarEliminar','modalConfirmarRestaurar'].forEach(cerrarModal);
});

// ── VER SEGUIMIENTO ───────────────────────────────────────
async function verSeguimiento(cod) {
    document.getElementById('ver_cod_seg').textContent      = cod;
    document.getElementById('ver_seg_loading').style.display   = 'block';
    document.getElementById('ver_seg_contenido').style.display = 'none';
    document.getElementById('modalVerSeg').style.display = 'flex';

    try {
        const resp = await fetch(`/dashboard/seguimientos/${cod}/detalle`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        const data = await resp.json();

        document.getElementById('ver_seg_tarjeta').innerHTML = `
            <div class="tarjeta-item">
                <span class="tarjeta-label">Técnico</span>
                <span class="tarjeta-valor">${data.tecnico ?? '—'}</span>
            </div>
            <div class="tarjeta-item">
                <span class="tarjeta-label">Placa</span>
                <span class="tarjeta-valor">
                    <span class="badge-placa">${data.placa ?? '—'}</span>
                </span>
            </div>
            <div class="tarjeta-item">
                <span class="tarjeta-label">Tipo</span>
                <span class="tarjeta-valor">
                    <span class="badge-tipo ${data.tipo_css ?? ''}">${data.tipo ?? '—'}</span>
                </span>
            </div>
            <div class="tarjeta-item">
                <span class="tarjeta-label">Fecha</span>
                <span class="tarjeta-valor">${data.fecha ?? '—'}</span>
            </div>
            <div class="tarjeta-item">
                <span class="tarjeta-label">Solicitud</span>
                <span class="tarjeta-valor">${data.solicitud ?? '—'}</span>
            </div>
        `;

        document.getElementById('ver_seg_obs').textContent = data.observacion || 'Sin observaciones.';

        const repEl = document.getElementById('ver_seg_repuestos');
        repEl.innerHTML = data.repuestos.length
            ? data.repuestos.map(r => `
                <div class="seg-rep-fila">
                    <span class="material-symbols-outlined" style="font-size:16px;color:#ff7b00;">build</span>
                    <span class="seg-rep-nombre">${r.nombre}</span>
                    <span class="seg-rep-qty">x${r.qty}</span>
                </div>`).join('')
            : '<p style="color:#666;font-size:13px;padding:10px;">Sin repuestos registrados.</p>';

        document.getElementById('ver_seg_loading').style.display   = 'none';
        document.getElementById('ver_seg_contenido').style.display = '';

    } catch(e) {
        console.error('Error verSeguimiento:', e);
        cerrarModal('modalVerSeg');
    }
}

// ── EDITAR SEGUIMIENTO ────────────────────────────────────
async function abrirEditarSeguimiento(cod) {
    editSegState = { cod_seg: cod, repuestos: [], eliminados: [], disponibles: [] };

    document.getElementById('edit_cod_seg').textContent      = cod;
    document.getElementById('edit_seg_loading').style.display   = 'block';
    document.getElementById('edit_seg_contenido').style.display = 'none';
    document.getElementById('modalEditarSeg').style.display  = 'flex';
    mostrarErrorEdit('');

    try {
        const resp = await fetch(`/seguimiento/${cod}/repuestos`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        const data = await resp.json();

        editSegState.cod_seg     = data.cod_seg;
        editSegState.disponibles = data.disponibles ?? [];
        editSegState.eliminados  = [];
        editSegState.repuestos   = (data.repuestos ?? []).map(r => ({ ...r, esNuevo: false }));

        // Tarjeta info
        document.getElementById('edit_seg_tarjeta').innerHTML = `
            <div class="tarjeta-item">
                <span class="tarjeta-label">Nota</span>
                <span class="tarjeta-valor">${cod}</span>
            </div>
        `;

        document.getElementById('edit_obs_seg').value = data.obs_seg ?? '';

        renderizarRepuestosEdit();
        poblarSelectEdit();

        document.getElementById('edit_seg_loading').style.display   = 'none';
        document.getElementById('edit_seg_contenido').style.display = '';

    } catch(e) {
        console.error('Error abrirEditarSeguimiento:', e);
        mostrarErrorEdit('Error al cargar los datos.');
        document.getElementById('edit_seg_loading').style.display   = 'none';
        document.getElementById('edit_seg_contenido').style.display = '';
    }
}

function renderizarRepuestosEdit() {
    const contenedor = document.getElementById('edit_lista_repuestos');
    if (editSegState.repuestos.length === 0) {
        contenedor.innerHTML = '<p style="color:#666;font-size:13px;padding:8px 0;">Sin repuestos en este seguimiento.</p>';
        return;
    }
    contenedor.innerHTML = editSegState.repuestos.map((r, idx) => {
        const maxSt = r.esNuevo ? r.stock_disponible : (r.stock_disponible + r.qty);
        return `
        <div class="edit-rep-fila" data-idx="${idx}">
            <span class="material-symbols-outlined" style="font-size:16px;color:#ff7b00;">build</span>
            <span class="edit-rep-nombre">${r.nombre}</span>
            <span class="edit-rep-stock">Stock: ${maxSt}</span>
            <input type="number" class="edit-rep-qty"
                   min="1" max="${maxSt}" value="${r.qty}"
                   onchange="cambiarQtyEdit(${idx}, this.value)">
            <button class="edit-btn-quitar" onclick="quitarRepuestoEdit(${idx})" title="Quitar">
                <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
            </button>
        </div>`;
    }).join('');
}

function poblarSelectEdit() {
    const sel = document.getElementById('edit_select_rep');
    const yaAgregados = editSegState.repuestos.map(r => r.cod_rep);
    sel.innerHTML = '<option value="">— Seleccionar repuesto —</option>' +
        editSegState.disponibles
            .filter(d => !yaAgregados.includes(d.cod))
            .map(d => `<option value="${d.cod}" data-stock="${d.stock}">${d.nombre} (Stock: ${d.stock})</option>`)
            .join('');
}

function cambiarQtyEdit(idx, val) {
    const r   = editSegState.repuestos[idx];
    const qty = parseInt(val) || 1;
    const max = r.esNuevo ? r.stock_disponible : (r.stock_disponible + r.qty);
    if (qty < 1)   { mostrarErrorEdit('Cantidad mínima: 1.'); return; }
    if (qty > max) { mostrarErrorEdit(`Stock insuficiente. Máximo: ${max}`); return; }
    mostrarErrorEdit('');
    editSegState.repuestos[idx].qty = qty;
}

function quitarRepuestoEdit(idx) {
    const r = editSegState.repuestos[idx];
    if (!r.esNuevo && r.cod_solicitudesrep) {
        editSegState.eliminados.push(r.cod_solicitudesrep);
    }
    editSegState.repuestos.splice(idx, 1);
    renderizarRepuestosEdit();
    poblarSelectEdit();
    mostrarErrorEdit('');
}

function editSegAgregarRepuesto() {
    const sel   = document.getElementById('edit_select_rep');
    const qty   = parseInt(document.getElementById('edit_qty_rep').value) || 1;
    const cod   = sel.value;
    if (!cod) { mostrarErrorEdit('Selecciona un repuesto.'); return; }

    const disp = editSegState.disponibles.find(d => d.cod === cod);
    if (!disp)  { mostrarErrorEdit('Repuesto no encontrado.'); return; }
    if (qty < 1)         { mostrarErrorEdit('Cantidad mínima: 1.'); return; }
    if (qty > disp.stock){ mostrarErrorEdit(`Stock insuficiente. Disponible: ${disp.stock}`); return; }

    mostrarErrorEdit('');
    editSegState.repuestos.push({
        cod_solicitudesrep: null,
        cod_rep:            cod,
        nombre:             disp.nombre,
        qty,
        stock_disponible:   disp.stock,
        esNuevo:            true,
    });
    sel.value = '';
    document.getElementById('edit_qty_rep').value = 1;
    renderizarRepuestosEdit();
    poblarSelectEdit();
}

function guardarEdicionSeg() {
    mostrarErrorEdit('');

    // Leer qtys del DOM
    document.querySelectorAll('#edit_lista_repuestos .edit-rep-fila').forEach(fila => {
        const idx   = parseInt(fila.dataset.idx);
        const input = fila.querySelector('.edit-rep-qty');
        if (input && editSegState.repuestos[idx]) {
            editSegState.repuestos[idx].qty = parseInt(input.value) || 1;
        }
    });

    const payload = {
        _token:     CSRF,
        obs_seg:    document.getElementById('edit_obs_seg')?.value ?? '',
        eliminados: editSegState.eliminados,
        repuestos:  editSegState.repuestos.map(r => ({
            cod_rep: r.cod_rep,
            qty:     r.qty,
            cod_sr:  r.esNuevo ? null : r.cod_solicitudesrep,
        })),
    };

    fetch(`/seguimiento/${editSegState.cod_seg}/repuestos/update`, {
        method:  'POST',
        headers: {
            'Content-Type':     'application/json',
            'Accept':           'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
    })
    .then(async r => {
        const text = await r.text();
        try { return JSON.parse(text); }
        catch(e) { throw new Error('Respuesta inesperada: ' + text.slice(0, 100)); }
    })
    .then(data => {
        if (data.ok) {
            cerrarModal('modalEditarSeg');
            location.reload();
        } else {
            mostrarErrorEdit(data.error || 'Error al guardar.');
        }
    })
    .catch(err => mostrarErrorEdit('Error de red: ' + err.message));
}

function mostrarErrorEdit(msg) {
    const el = document.getElementById('edit_seg_error');
    if (!el) return;
    el.textContent    = msg;
    el.style.display  = msg ? 'block' : 'none';
}

// ── ELIMINAR ──────────────────────────────────────────────
let _idEliminar = null;
function confirmarEliminar(cod, fecha) {
    _idEliminar = cod;
    document.getElementById('confirmFechaSeg').textContent = fecha;
    document.getElementById('modalConfirmarEliminar').style.display = 'flex';
}
function ejecutarEliminar() {
    if (!_idEliminar) return;
    const f = document.getElementById('formEliminar');
    f.action = `/dashboard/seguimientos/${_idEliminar}/eliminar`;
    f.style.display = 'block';
    f.submit();
}

// ── RESTAURAR ─────────────────────────────────────────────
let _idRestaurar = null;
function confirmarRestaurar(cod, fecha) {
    _idRestaurar = cod;
    document.getElementById('confirmFechaRestaurar').textContent = fecha;
    document.getElementById('modalConfirmarRestaurar').style.display = 'flex';
}
function ejecutarRestaurar() {
    if (!_idRestaurar) return;
    const f = document.getElementById('formRestaurar');
    f.action = `/dashboard/seguimientos/${_idRestaurar}/restaurar`;
    f.style.display = 'block';
    f.submit();
}
</script>

@endsection