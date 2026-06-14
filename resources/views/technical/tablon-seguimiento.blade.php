@php
    $posiciones = [
        ['top' => 45,  'left' => 70],
        ['top' => 45,  'left' => 260],
        ['top' => 45,  'left' => 450],
        ['top' => 45,  'left' => 640],
        ['top' => 240, 'left' => 70],
        ['top' => 240, 'left' => 260],
        ['top' => 240, 'left' => 450],
        ['top' => 240, 'left' => 640],
        ['top' => 435, 'left' => 165],
        ['top' => 435, 'left' => 355],
        ['top' => 435, 'left' => 545],
    ];
@endphp

@extends('layouts.slidebarTecnico')

@push('styles')
    @vite('resources/css/tablon-seguimiento.css')
@endpush

@section('title', 'Técnico | Seguimiento')

@section('content')

<div class="INVheader">
    <h1 class="INVtitle">SEGUIMIENTO</h1>
</div>

<div class="content-seguimiento">

    @php $origen = $origen ?? session('origen_mantenimiento', 'asignados'); @endphp

    {{-- Botón volver --}}
    <a href="{{ $origen === 'finalizados'
        ? route('tecnico.finalizados')
        : route('tecnico.asignaciones') }}"
        class="btnVolverMant">
        <span class="material-symbols-outlined" style="font-size:20px;vertical-align:middle;">arrow_back</span>
        Volver
    </a>

    {{-- ── TABS ── --}}
    <div class="tabs-seg">
        <button class="tab-seg-btn active" onclick="cambiarTab('activos', this)">
            <span class="material-symbols-outlined" style="font-size:16px;">sticky_note_2</span>
            Seguimientos
            <span class="tab-seg-count">{{ $seguimientos->count() }}</span>
        </button>
        <button class="tab-seg-btn" onclick="cambiarTab('papelera', this)">
            <span class="material-symbols-outlined" style="font-size:16px;">delete_sweep</span>
            Papelera
            <span class="tab-seg-count {{ $eliminados->count() > 0 ? 'has-items' : '' }}">
                {{ $eliminados->count() }}
            </span>
        </button>
    </div>

    {{-- ── TAB ACTIVOS: TABLÓN ── --}}
    <div id="tab-activos">
        <div class="tablero">

            @foreach ($seguimientos as $i => $seg)
                @php $pos = $posiciones[$i % count($posiciones)]; @endphp

                <div class="nota"
                    style="top: {{ $pos['top'] }}px; left: {{ $pos['left'] }}px;"
                    data-fecha="{{ \Carbon\Carbon::parse($seg->fcs_seg)->format('d/m/Y') }}"
                    data-fecha-input="{{ \Carbon\Carbon::parse($seg->fcs_seg)->format('Y-m-d') }}"
                    data-obs="{{ $seg->obs_seg }}"
                    data-cod="{{ $seg->cod_seg }}"
                    data-repuestos='@json(
                        $seg->repuestosUsados->map(fn($r) => [
                            "nombre" => ($r->repuesto->nom_rep ?? "—")." ".($r->repuesto->mod_rep ?? ""),
                            "qty"    => $r->can_sol,
                        ])
                    )'>

                    <span class="nota-fecha">
                        {{ \Carbon\Carbon::parse($seg->fcs_seg)->format('d/m/Y') }}
                    </span>

                    {{-- Botones hover solo si no está finalizado --}}
                    @if($origen !== 'finalizados')
                    <div class="nota-acciones">
                        <button class="nota-btn nota-btn-edit"
                            title="Editar"
                            onclick="abrirModalEditar(this.closest('.nota'))">
                            <span class="material-symbols-outlined">edit</span>
                        </button>

                        <button class="nota-btn nota-btn-delete"
                            title="Eliminar"
                            onclick="abrirModalEliminar(this.closest('.nota'))">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                    @endif

                </div>
            @endforeach

            @if($origen === 'finalizados')
                <span class="btn-nuevo disabled" style="pointer-events:none;opacity:.4;">+ Nuevo</span>
            @else
                <a href="{{ route('tecnico.seguimiento.nuevo', $solicitud->cod_solicitudes) }}"
                   class="btn-nuevo">+ Nuevo</a>
            @endif

        </div>
    </div>

    {{-- ── TAB PAPELERA ── --}}
    <div id="tab-papelera" style="display:none;">
        <div class="papelera-header">
            <span class="material-symbols-outlined" style="color:#dc3545;font-size:22px;">delete_sweep</span>
            <span>Seguimientos eliminados — pueden restaurarse en cualquier momento.</span>
        </div>

        @if($eliminados->isEmpty())
            <div style="text-align:center;color:#aaa;padding:60px 0;">
                <span class="material-symbols-outlined"
                    style="font-size:52px;display:block;margin-bottom:10px;opacity:.3;">
                    delete_outline
                </span>
                La papelera está vacía.
            </div>
        @else
            <div class="table-wrapper">
                <table id="tablaPapelera">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Observaciones</th>
                            <th>Eliminado el</th>
                            <th>Restaurado el</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($eliminados as $e)
                        <tr>
                            <td class="id-usuario">
                                <span class="material-symbols-outlined icono-perfil"
                                    style="color:#666;">sticky_note_2</span>
                                {{ $e->cod_seg }}
                            </td>
                            <td>{{ \Carbon\Carbon::parse($e->fcs_seg)->format('d/m/Y') }}</td>
                            <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;
                                       white-space:nowrap;color:#aaa;font-size:13px;">
                                {{ $e->obs_seg ?? '—' }}
                            </td>
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
                                {{-- FIX: cod_seg es string, va entre comillas --}}
                                <button type="button" class="btn-restaurar"
                                    onclick="abrirModalRestaurar(
                                        '{{ $e->cod_seg }}',
                                        '{{ \Carbon\Carbon::parse($e->fcs_seg)->format('d/m/Y') }}'
                                    )">
                                    <span class="material-symbols-outlined"
                                        style="font-size:14px;vertical-align:middle;">restore</span>
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

{{-- ══════════════════════════════════════════════════════
     MODAL: Ver nota
════════════════════════════════════════════════════════ --}}
<div id="modalSeguimiento" class="jhire-modal" style="display:none;">
    <div class="jhire-modal-content" onclick="event.stopPropagation()">
        <button class="jhire-close" onclick="cerrarModalLocal('modalSeguimiento')">✖</button>
        <h2>Detalle del Seguimiento</h2>
        <div class="input-box">
            <label>Fecha:</label>
            <input type="text" id="segFecha" readonly>
        </div>
        <div class="input-box">
            <label>Observaciones:</label>
            <textarea id="segObs" readonly></textarea>
        </div>
        <div class="input-box">
            <label>Repuestos solicitados:</label>
            <div id="segRepuestos" class="seg-repuestos-list"></div>
        </div>
        <button class="btn-guardar" onclick="cerrarModalLocal('modalSeguimiento')">Cerrar</button>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL: Editar repuestos de la nota
════════════════════════════════════════════════════════ --}}
<div id="modalEditar" class="jhire-modal" style="display:none;">
    <div class="jhire-modal-content jhire-modal-lg" onclick="event.stopPropagation()">
        <button class="jhire-close" onclick="cerrarModalLocal('modalEditar')">✖</button>
        <h2 style="margin-bottom:4px;">Editar Repuestos</h2>
        <p style="color:#888;font-size:13px;margin-bottom:18px;" id="editSubtitulo">—</p>

        {{-- Observaciones --}}
        <div class="input-box">
            <label>Observaciones:</label>
            <textarea id="editObs" name="obs_seg" style="height:60px;"></textarea>
        </div>

        {{-- Lista de repuestos actuales --}}
        <label style="font-weight:bold;font-size:14px;color:#5e3e00;display:block;margin-bottom:8px;">
            Repuestos en esta nota:
        </label>
        <div id="listaRepuestosEditar" class="seg-repuestos-list" style="max-height:260px;"></div>

        {{-- Agregar nuevo repuesto --}}
        <div class="edit-agregar-wrap">
            <select id="selectNuevoRep" class="edit-select-rep">
                <option value="">— Seleccionar repuesto —</option>
            </select>
            <input type="number" id="inputNuevoQty" min="1" value="1"
                   class="edit-qty-input" placeholder="Qty">
            <button class="edit-btn-agregar" onclick="agregarRepuestoNuevo()">
                <span class="material-symbols-outlined" style="font-size:16px;">add</span>
                Agregar
            </button>
        </div>
        <p id="editError" style="color:#ef4444;font-size:13px;margin-top:8px;display:none;"></p>

        <div class="modal-btns">
            <button class="btn-guardar" onclick="guardarEdicion()">
                <span class="material-symbols-outlined"
                    style="font-size:16px;vertical-align:middle;">save</span>
                Guardar cambios
            </button>
            <button class="btn-cancelar-modal" onclick="cerrarModalLocal('modalEditar')">
                Cancelar
            </button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL: Confirmar eliminación
════════════════════════════════════════════════════════ --}}
<div id="modalConfirmarEliminar" class="seg-modal-overlay" style="display:none;">
    <div class="modal-confirm-box" onclick="event.stopPropagation()">
        <div class="modal-confirm-icon">
            <span class="material-symbols-outlined">delete_forever</span>
        </div>
        <h3 class="modal-confirm-titulo">¿Eliminar seguimiento?</h3>
        <p class="modal-confirm-texto">
            El seguimiento del <strong id="confirmFechaSeg">—</strong>
            se moverá a la papelera.<br>
            <span style="color:#aaa;font-size:13px;">
                Podrás restaurarlo desde la pestaña "Papelera".
            </span>
        </p>
        <div class="modal-confirm-botones">
            <button class="btn-confirm-cancelar"
                onclick="cerrarModalConfirmarEliminar()">Cancelar</button>
            <button class="btn-confirm-eliminar"
                onclick="ejecutarEliminarSeg()">Sí, eliminar</button>
        </div>
        <form id="formEliminarSeg" method="POST" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL: Confirmar restauración
════════════════════════════════════════════════════════ --}}
<div id="modalConfirmarRestaurar" class="seg-modal-overlay" style="display:none;">
    <div class="modal-confirm-box" onclick="event.stopPropagation()">
        <div class="modal-confirm-icon restaurar">
            <span class="material-symbols-outlined">restore</span>
        </div>
        <h3 class="modal-confirm-titulo">¿Restaurar seguimiento?</h3>
        <p class="modal-confirm-texto">
            El seguimiento del <strong id="confirmFechaRestaurar">—</strong>
            volverá a aparecer en el tablón.
        </p>
        <div class="modal-confirm-botones">
            <button class="btn-confirm-cancelar"
                onclick="cerrarModalRestaurar()">Cancelar</button>
            <button class="btn-confirm-restaurar"
                onclick="ejecutarRestaurarSeg()">Sí, restaurar</button>
        </div>
        <form id="formRestaurarSeg" method="POST" style="display:none;">@csrf</form>
    </div>
</div>


<script>
// ── Tabs ──────────────────────────────────────────────────
function cambiarTab(tab, btn) {
    document.getElementById('tab-activos').style.display  = tab === 'activos'  ? 'block' : 'none';
    document.getElementById('tab-papelera').style.display = tab === 'papelera' ? 'block' : 'none';
    document.querySelectorAll('.tab-seg-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

// ── Ver nota ──────────────────────────────────────────────
// Recibe el elemento .nota y lee sus data-attributes
function abrirNota(el) {
    document.getElementById('segFecha').value = el.dataset.fecha || '';
    document.getElementById('segObs').value   = el.dataset.obs   || 'Sin observaciones';

    const repuestos = JSON.parse(el.dataset.repuestos || '[]');
    const repDiv    = document.getElementById('segRepuestos');
    repDiv.innerHTML = repuestos.length === 0
        ? '<p style="color:#666;font-size:13px;">Sin repuestos registrados</p>'
        : repuestos.map(r => `
            <div class="seg-rep-item">
                <span class="material-symbols-outlined" style="font-size:15px;color:#ff7b00;">build</span>
                <span>${r.nombre}</span>
                <span class="seg-rep-qty">x${r.qty}</span>
            </div>`).join('');

    abrirModal('modalSeguimiento');
}

// ═══════════════════════════════════════════════════════
// EDITAR REPUESTOS
// ═══════════════════════════════════════════════════════

let editState = {
    cod_seg:    null,
    repuestos:  [],   // [{cod_solicitudesrep, cod_rep, nombre, qty, stock_disponible, esNuevo}]
    eliminados: [],   // cod_solicitudesrep a borrar
    disponibles: [],  // catálogo completo
};

// Abre el modal cargando datos vía AJAX
function abrirModalEditar(notaEl) {
    const cod = notaEl.dataset.cod;
    mostrarError('');

    fetch(`/seguimiento/${cod}/repuestos`)
        .then(r => r.json())
        .then(data => {
            editState.cod_seg     = data.cod_seg;
            editState.eliminados  = [];
            editState.disponibles = data.disponibles;

            // Clonar repuestos para edición local
            editState.repuestos = data.repuestos.map(r => ({ ...r, esNuevo: false }));

            document.getElementById('editSubtitulo').textContent =
                `Nota: ${notaEl.dataset.fecha}`;
            document.getElementById('editObs').value = data.obs_seg || '';

            renderizarRepuestos();
            poblarSelectNuevo();
            abrirModal('modalEditar');
        })
        .catch(() => mostrarError('Error al cargar los datos.'));
}

// Renderiza la lista de repuestos en el modal
function renderizarRepuestos() {
    const contenedor = document.getElementById('listaRepuestosEditar');

    if (editState.repuestos.length === 0) {
        contenedor.innerHTML =
            '<p style="color:#666;font-size:13px;padding:8px 0;">Sin repuestos en esta nota.</p>';
        return;
    }

    contenedor.innerHTML = editState.repuestos.map((r, idx) => `
        <div class="edit-rep-fila" data-idx="${idx}">
            <span class="material-symbols-outlined"
                style="font-size:16px;color:#ff7b00;">build</span>
            <span class="edit-rep-nombre">${r.nombre}</span>
            <span class="edit-rep-stock">
                Stock: ${r.esNuevo ? r.stock_disponible : r.stock_disponible + r.qty}
            </span>
            <input type="number" class="edit-rep-qty"
                   min="1"
                   max="${r.esNuevo ? r.stock_disponible : r.stock_disponible + r.qty}"
                   value="${r.qty}"
                   onchange="cambiarQty(${idx}, this.value)">
            <button class="edit-btn-quitar"
                title="Quitar repuesto"
                onclick="quitarRepuesto(${idx})">
                <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
            </button>
        </div>
    `).join('');
}

// Cambia cantidad de un repuesto existente
function cambiarQty(idx, val) {
    const r     = editState.repuestos[idx];
    const qty   = parseInt(val) || 1;
    const maxSt = r.esNuevo ? r.stock_disponible : r.stock_disponible + r.qty;

    if (qty < 1) {
        mostrarError('La cantidad mínima es 1.');
        return;
    }
    if (qty > maxSt) {
        mostrarError(`Stock insuficiente para "${r.nombre}". Máximo: ${maxSt}`);
        return;
    }
    mostrarError('');
    editState.repuestos[idx].qty = qty;
}

// Quita un repuesto de la nota (marca para eliminar si ya existe en BD)
function quitarRepuesto(idx) {
    const r = editState.repuestos[idx];
    if (!r.esNuevo && r.cod_solicitudesrep) {
        editState.eliminados.push(r.cod_solicitudesrep);
    }
    editState.repuestos.splice(idx, 1);
    renderizarRepuestos();
    mostrarError('');
}

// Puebla el select con repuestos disponibles (excluye los ya agregados)
function poblarSelectNuevo() {
    const sel       = document.getElementById('selectNuevoRep');
    const yaAgregados = editState.repuestos.map(r => r.cod_rep);

    sel.innerHTML = '<option value="">— Seleccionar repuesto —</option>' +
        editState.disponibles
            .filter(d => !yaAgregados.includes(d.cod))
            .map(d => `<option value="${d.cod}" data-stock="${d.stock}">
                ${d.nombre} (Stock: ${d.stock})
            </option>`)
            .join('');
}

// Agrega un repuesto nuevo a la lista local
function agregarRepuestoNuevo() {
    const sel = document.getElementById('selectNuevoRep');
    const qty = parseInt(document.getElementById('inputNuevoQty').value) || 1;
    const cod = sel.value;

    if (!cod) { mostrarError('Selecciona un repuesto.'); return; }

    const disp = editState.disponibles.find(d => d.cod === cod);
    if (!disp)  { mostrarError('Repuesto no encontrado.'); return; }

    if (qty < 1)          { mostrarError('La cantidad mínima es 1.'); return; }
    if (qty > disp.stock) {
        mostrarError(`Stock insuficiente. Disponible: ${disp.stock}`);
        return;
    }

    mostrarError('');
    editState.repuestos.push({
        cod_solicitudesrep: null,
        cod_rep:            cod,
        nombre:             disp.nombre,
        qty:                qty,
        stock_disponible:   disp.stock,
        esNuevo:            true,
    });

    // Reset controles
    sel.value = '';
    document.getElementById('inputNuevoQty').value = 1;

    renderizarRepuestos();
    poblarSelectNuevo(); // actualiza para excluir el recién agregado
}

// Envía los cambios al servidor
function guardarEdicion() {
    mostrarError('');

    // Leer qtys del DOM solo si los elementos existen
    document.querySelectorAll('.edit-rep-fila').forEach(fila => {
        const idx     = parseInt(fila.dataset.idx);
        const input   = fila.querySelector('.edit-rep-qty');
        if (!input) return;                          // ← guard
        const qty = parseInt(input.value) || 1;
        if (editState.repuestos[idx]) editState.repuestos[idx].qty = qty;
    });

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfMeta) {
        mostrarError('Error: no se encontró el token CSRF.');
        return;
    }

    const payload = {
        _token:     csrfMeta.content,
        obs_seg:    document.getElementById('editObs')?.value ?? '',
        eliminados: editState.eliminados,
        repuestos:  editState.repuestos.map(r => ({
            cod_rep: r.cod_rep,
            qty:     r.qty,
            cod_sr:  r.esNuevo ? null : r.cod_solicitudesrep,
        })),
    };

    fetch(`/seguimiento/${editState.cod_seg}/repuestos/update`, {
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
        try {
            return JSON.parse(text);
        } catch(e) {
            throw new Error('Respuesta no es JSON: ' + text.slice(0, 200));
        }
    })
    .then(data => {
        if (data.ok) {
            cerrarModalLocal('modalEditar');
            location.reload();
        } else {
            mostrarError(data.error || 'Error al guardar.');
        }
    })
    .catch(err => {
        mostrarError('Error de red: ' + err.message);
    });
}

function mostrarError(msg) {
    const el = document.getElementById('editError');
    if (!el) return;
    el.textContent = msg;
    el.style.display = msg ? 'block' : 'none';
}

// ── Eliminar ──────────────────────────────────────────────
function abrirModalEliminar(notaEl) {
    const cod   = notaEl.dataset.cod;
    const fecha = notaEl.dataset.fecha;
    document.getElementById('confirmFechaSeg').textContent = fecha;
    document.getElementById('formEliminarSeg').action      = `/seguimiento/${cod}`;
    mostrarOverlay('modalConfirmarEliminar');
}
function cerrarModalConfirmarEliminar() { ocultarOverlay('modalConfirmarEliminar'); }
function ejecutarEliminarSeg()          { document.getElementById('formEliminarSeg').submit(); }

// ── Restaurar ─────────────────────────────────────────────
function abrirModalRestaurar(cod, fecha) {
    document.getElementById('confirmFechaRestaurar').textContent = fecha;
    document.getElementById('formRestaurarSeg').action           = `/seguimiento/${cod}/restore`;
    mostrarOverlay('modalConfirmarRestaurar');
}
function cerrarModalRestaurar() { ocultarOverlay('modalConfirmarRestaurar'); }
function ejecutarRestaurarSeg() { document.getElementById('formRestaurarSeg').submit(); }

// ── Helpers ───────────────────────────────────────────────
function abrirModal(id)       { document.getElementById(id).style.display = 'flex'; }
function cerrarModalLocal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}
function mostrarOverlay(id)   { document.getElementById(id).style.display = 'flex'; }
function ocultarOverlay(id)   { document.getElementById(id).style.display = 'none'; }

// ── Click en cuerpo de nota → abrir modal ver ─────────────
document.querySelectorAll('.nota').forEach(nota => {
    nota.addEventListener('click', function(e) {
        // Si el click viene de un botón o de .nota-acciones, ignorar
        if (e.target.tagName === 'BUTTON' || 
            e.target.tagName === 'SPAN' ||
            e.target.closest('.nota-btn') || 
            e.target.closest('.nota-acciones')) {
            return;
        }
        abrirNota(this);
    });
});

// ── Cerrar overlays con click en el fondo ─────────────────
['modalConfirmarEliminar', 'modalConfirmarRestaurar'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) ocultarOverlay(id);
    });
});

// ── Cerrar jhire-modals con click en el fondo ─────────────
['modalSeguimiento', 'modalEditar'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
        el.addEventListener('click', function(e) {
            if (e.target === this) cerrarModalLocal(id);
        });
    }
});

document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    ['modalSeguimiento', 'modalEditar'].forEach(cerrarModalLocal);
    cerrarModalConfirmarEliminar();
    cerrarModalRestaurar();
});
</script>

@endsection