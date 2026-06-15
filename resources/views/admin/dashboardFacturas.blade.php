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

@php
    $sol      = $mantenimiento->solicitud;
    $cliente  = $sol?->cliente;
    $vehiculo = $sol?->vehiculo;

    // Pre-cargar ítems del mantenimiento
    $serviciosMant = $mantenimiento->servicios ?? collect();
    $repuestosMant = $mantenimiento->repuestos ?? collect();
@endphp

<div class="fac-page">

    {{-- ══ CABECERA ══ --}}
    <div class="fac-topbar">
        <div class="fac-topbar-left">
            <a href="{{ route('mantenimientos.index') }}" class="btn-volver">
                <span class="material-symbols-outlined" style="font-size:18px;vertical-align:middle;">arrow_back</span>
                Volver
            </a>
            <div class="fac-titulo-wrap">
                <span class="material-symbols-outlined fac-titulo-icono">receipt_long</span>
                <div>
                    <h2 class="fac-titulo">Nueva Factura</h2>
                    <p class="fac-subtitulo">
                        Orden de trabajo
                        <span class="fac-cod-badge">{{ $mantenimiento->cod_mantenimientos }}</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="fac-topbar-right">
            <button type="button" class="btn-preview" onclick="togglePreview()">
                <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">visibility</span>
                Vista previa
            </button>
        </div>
    </div>

    <form id="formFactura"
          action="{{ route('facturas.guardar', $mantenimiento->cod_mantenimientos) }}"
          method="POST">
        @csrf

        <div class="fac-layout">

            {{-- ══ COLUMNA IZQUIERDA: datos + ítems ══ --}}
            <div class="fac-col-main">

                {{-- Bloque: datos de la factura --}}
                <div class="fac-card">
                    <div class="fac-card-header">
                        <span class="material-symbols-outlined fac-card-icono">assignment</span>
                        Datos de la factura
                    </div>
                    <div class="fac-card-body">
                        <div class="fac-grid-3">
                            <div class="fac-campo">
                                <label>Nº de Factura <span class="req">*</span></label>
                                <input type="text"
                                       name="nfa_fac"
                                       value="{{ old('nfa_fac') }}"
                                       placeholder="Ej: 001-001-0000001"
                                       required>
                            </div>
                            <div class="fac-campo">
                                <label>Fecha de emisión <span class="req">*</span></label>
                                <input type="date"
                                       name="fec_fac"
                                       value="{{ old('fec_fac', now()->format('Y-m-d')) }}"
                                       required>
                            </div>
                            <div class="fac-campo">
                                <label>Punto de emisión <span class="req">*</span></label>
                                <input type="text"
                                       name="pto_fac"
                                       value="{{ old('pto_fac', '001') }}"
                                       placeholder="Ej: 001"
                                       required>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bloque: datos del cliente --}}
                <div class="fac-card">
                    <div class="fac-card-header">
                        <span class="material-symbols-outlined fac-card-icono">person</span>
                        Cliente
                    </div>
                    <div class="fac-card-body">
                        <div class="fac-cliente-info">
                            <span class="material-symbols-outlined fac-avatar">account_circle</span>
                            <div>
                                <p class="fac-cliente-nombre">
                                    {{ $cliente?->nom_cli }} {{ $cliente?->app_cli ?? '' }}
                                </p>
                                <p class="fac-cliente-sub">
                                    <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">directions_car</span>
                                    {{ $vehiculo?->mar_veh }} {{ $vehiculo?->mod_veh }}
                                    @if($vehiculo?->ani_veh) ({{ $vehiculo->ani_veh }}) @endif
                                    &nbsp;·&nbsp;
                                    Placa: {{ $vehiculo?->cod_vehiculos ?? '—' }}
                                </p>
                            </div>
                        </div>
                        <div class="fac-grid-2" style="margin-top:14px;">
                            <div class="fac-campo">
                                <label>NIT / CI del cliente <span class="req">*</span></label>
                                <input type="text"
                                       name="nit_cli"
                                       value="{{ old('nit_cli', $cliente?->nit_cli ?? $cliente?->ci_cli ?? '') }}"
                                       placeholder="Número de NIT o CI"
                                       required>
                            </div>
                            <div class="fac-campo">
                                <label>Razón social / Nombre factura</label>
                                <input type="text"
                                       name="razon_social"
                                       value="{{ old('razon_social', trim(($cliente?->nom_cli ?? '').' '.($cliente?->app_cli ?? ''))) }}"
                                       placeholder="Nombre o empresa para la factura">
                            </div>
                        </div>
                        <input type="hidden"
                               name="cod_clientes_fac"
                               value="{{ $cliente?->cod_clientes }}">
                    </div>
                </div>

                {{-- Bloque: ítems de la factura --}}
                <div class="fac-card">
                    <div class="fac-card-header">
                        <span class="material-symbols-outlined fac-card-icono">list_alt</span>
                        Detalle de servicios y repuestos
                    </div>
                    <div class="fac-card-body" style="padding-bottom:8px;">

                        {{-- Cabecera de la tabla de ítems --}}
                        <div class="fac-items-header">
                            <span class="col-concepto">Concepto</span>
                            <span class="col-cant">Cant.</span>
                            <span class="col-precio">P. Unit. (Bs)</span>
                            <span class="col-sub">Subtotal (Bs)</span>
                            <span class="col-accion"></span>
                        </div>

                        <div id="listaItems">
                            {{-- Servicios del mantenimiento --}}
                            @foreach($serviciosMant as $idx => $srv)
                                <div class="fac-item-fila" data-idx="{{ $idx }}">
                                    <div class="col-concepto">
                                        <input type="text"
                                               name="items[{{ $idx }}][concepto]"
                                               class="fac-input concepto-input"
                                               value="{{ old("items.$idx.concepto", $srv->nom_ser ?? $srv->pivot->concepto ?? $srv->con_det ?? '') }}"
                                               placeholder="Descripción del concepto"
                                               required>
                                        <span class="fac-badge-origen srv">Servicio</span>
                                    </div>
                                    <div class="col-cant">
                                        <input type="number"
                                               name="items[{{ $idx }}][cantidad]"
                                               class="fac-input cant-input"
                                               value="{{ old("items.$idx.cantidad", $srv->pivot->cantidad ?? $srv->can_det ?? 1) }}"
                                               min="1" step="1"
                                               oninput="recalcular(this)">
                                    </div>
                                    <div class="col-precio">
                                        <input type="number"
                                               name="items[{{ $idx }}][precio]"
                                               class="fac-input precio-input"
                                               value="{{ old("items.$idx.precio", number_format($srv->pivot->pre_uni ?? $srv->pre_ser ?? $srv->pun_det ?? 0, 2, '.', '')) }}"
                                               min="0" step="0.01"
                                               oninput="recalcular(this)">
                                    </div>
                                    <div class="col-sub">
                                        <span class="fac-subtotal">
                                            Bs {{ number_format(($srv->pivot->cantidad ?? $srv->can_det ?? 1) * ($srv->pivot->pre_uni ?? $srv->pre_ser ?? $srv->pun_det ?? 0), 2) }}
                                        </span>
                                    </div>
                                    <div class="col-accion">
                                        <button type="button" class="btn-quitar-item"
                                                onclick="quitarItem(this)">
                                            <span class="material-symbols-outlined" style="font-size:18px;">remove_circle</span>
                                        </button>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Repuestos del mantenimiento --}}
                            @foreach($repuestosMant as $rep)
                                @php $idx = $loop->index + $serviciosMant->count(); @endphp
                                <div class="fac-item-fila" data-idx="{{ $idx }}">
                                    <div class="col-concepto">
                                        <input type="text"
                                               name="items[{{ $idx }}][concepto]"
                                               class="fac-input concepto-input"
                                               value="{{ old("items.$idx.concepto", $rep->nom_rep ?? $rep->pivot->concepto ?? $rep->con_det ?? '') }}"
                                               placeholder="Descripción del concepto"
                                               required>
                                        <span class="fac-badge-origen rep">Repuesto</span>
                                        <input type="hidden"
                                               name="items[{{ $idx }}][cod_repuesto]"
                                               value="{{ $rep->cod_repuestos ?? $rep->cod_repuestos_det ?? '' }}">
                                    </div>
                                    <div class="col-cant">
                                        <input type="number"
                                               name="items[{{ $idx }}][cantidad]"
                                               class="fac-input cant-input"
                                               value="{{ old("items.$idx.cantidad", $rep->pivot->cantidad ?? $rep->can_det ?? 1) }}"
                                               min="1" step="1"
                                               oninput="recalcular(this)">
                                    </div>
                                    <div class="col-precio">
                                        <input type="number"
                                               name="items[{{ $idx }}][precio]"
                                               class="fac-input precio-input"
                                               value="{{ old("items.$idx.precio", number_format($rep->pivot->pre_uni ?? $rep->pre_rep ?? $rep->pun_det ?? 0, 2, '.', '')) }}"
                                               min="0" step="0.01"
                                               oninput="recalcular(this)">
                                    </div>
                                    <div class="col-sub">
                                        <span class="fac-subtotal">
                                            Bs {{ number_format(($rep->pivot->cantidad ?? $rep->can_det ?? 1) * ($rep->pivot->pre_uni ?? $rep->pre_rep ?? $rep->pun_det ?? 0), 2) }}
                                        </span>
                                    </div>
                                    <div class="col-accion">
                                        <button type="button" class="btn-quitar-item"
                                                onclick="quitarItem(this)">
                                            <span class="material-symbols-outlined" style="font-size:18px;">remove_circle</span>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>{{-- /listaItems --}}

                        {{-- Botón agregar ítem manual --}}
                        <button type="button" class="btn-agregar-item-fac" onclick="agregarItemManual()">
                            <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">add_circle</span>
                            Agregar ítem manual
                        </button>

                    </div>
                </div>

                {{-- Bloque: observaciones --}}
                <div class="fac-card">
                    <div class="fac-card-header">
                        <span class="material-symbols-outlined fac-card-icono">notes</span>
                        Observaciones
                    </div>
                    <div class="fac-card-body">
                        <textarea name="obs_fac"
                                  rows="2"
                                  class="fac-textarea"
                                  placeholder="Notas internas o condiciones de pago...">{{ old('obs_fac') }}</textarea>
                    </div>
                </div>

            </div>{{-- /fac-col-main --}}

            {{-- ══ COLUMNA DERECHA: resumen + acciones ══ --}}
            <div class="fac-col-side">

                {{-- Resumen numérico --}}
                <div class="fac-card fac-resumen">
                    <div class="fac-card-header">
                        <span class="material-symbols-outlined fac-card-icono">summarize</span>
                        Resumen
                    </div>
                    <div class="fac-card-body">
                        <div class="fac-resumen-fila">
                            <span>Subtotal</span>
                            <span id="resSubtotal">Bs 0.00</span>
                        </div>
                        <div class="fac-resumen-fila" id="filaDescuento" style="display:none;">
                            <span>Descuento</span>
                            <span id="resDescuento" style="color:#e57373;">– Bs 0.00</span>
                        </div>
                        <div class="fac-resumen-sep"></div>
                        <div class="fac-resumen-total">
                            <span>TOTAL</span>
                            <span id="resTotal" class="fac-total-num">Bs 0.00</span>
                        </div>
                        <input type="hidden" name="total_fac" id="totalHidden" value="0">

                        {{-- Descuento opcional --}}
                        <div style="margin-top:14px;">
                            <label class="fac-label-desc">Descuento (%)</label>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <input type="number"
                                       id="inputDescuento"
                                       name="descuento_pct"
                                       min="0" max="100" step="0.5"
                                       value="{{ old('descuento_pct', 0) }}"
                                       class="fac-input-desc"
                                       oninput="recalcularTotal()">
                                <span style="color:#888;font-size:13px;">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Datos del mantenimiento (solo lectura) --}}
                <div class="fac-card fac-info-mant">
                    <div class="fac-card-header">
                        <span class="material-symbols-outlined fac-card-icono">build_circle</span>
                        Mantenimiento origen
                    </div>
                    <div class="fac-card-body">
                        <div class="fac-info-fila">
                            <span class="fac-info-label">Código</span>
                            <span class="fac-info-valor">{{ $mantenimiento->cod_mantenimientos }}</span>
                        </div>
                        <div class="fac-info-fila">
                            <span class="fac-info-label">Tipo</span>
                            <span class="fac-info-valor">{{ $sol?->tma_sol ?? '—' }}</span>
                        </div>
                        <div class="fac-info-fila">
                            <span class="fac-info-label">F. Inicio</span>
                            <span class="fac-info-valor">
                                {{ $mantenimiento->fec_ini_man ? \Carbon\Carbon::parse($mantenimiento->fec_ini_man)->format('d/m/Y') : '—' }}
                            </span>
                        </div>
                        <div class="fac-info-fila">
                            <span class="fac-info-label">F. Fin</span>
                            <span class="fac-info-valor">
                                {{ $mantenimiento->fec_fin_man ? \Carbon\Carbon::parse($mantenimiento->fec_fin_man)->format('d/m/Y') : '—' }}
                            </span>
                        </div>
                        <div class="fac-info-fila">
                            <span class="fac-info-label">Total orden</span>
                            <span class="fac-info-valor" style="color:#4caf50;font-weight:700;">
                                Bs {{ number_format($mantenimiento->total_man ?? 0, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Botones de acción --}}
                <div class="fac-acciones">
                    <button type="submit" class="btn-emitir">
                        <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">check_circle</span>
                        Emitir factura
                    </button>
                    <a href="{{ route('mantenimientos.index') }}" class="btn-cancelar-fac">
                        Cancelar
                    </a>
                </div>

            </div>{{-- /fac-col-side --}}

        </div>{{-- /fac-layout --}}
    </form>

</div>{{-- /fac-page --}}


{{-- ══ MODAL VISTA PREVIA ══ --}}
<div id="modalPreview"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);
            z-index:3000;justify-content:center;align-items:center;">
    <div class="preview-box">
        <div class="preview-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="material-symbols-outlined" style="color:#ff7b00;font-size:22px;">receipt_long</span>
                <strong style="color:#ff7b00;font-size:16px;">Vista previa — Factura</strong>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <button type="button" class="btn-imprimir-prev" onclick="imprimirPreview()">
                    <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">print</span>
                    Imprimir
                </button>
                <span onclick="togglePreview()"
                      style="font-size:26px;cursor:pointer;color:#ccc;line-height:1;">&times;</span>
            </div>
        </div>

        <div class="preview-body" id="prevContenido">
            {{-- Encabezado de la empresa --}}
            <div class="prev-empresa">
                <div class="prev-empresa-logo">
                    <span class="material-symbols-outlined" style="font-size:36px;color:#ff7b00;">garage</span>
                    <div>
                        <p class="prev-empresa-nombre">Taller Automotriz JHIRE</p>
                        <p class="prev-empresa-sub">Servicio técnico automotriz integral</p>
                    </div>
                </div>
                <div class="prev-fac-meta" id="prevFacMeta">
                    <p><strong>FACTURA</strong></p>
                    <p id="prevNroFac">Nº —</p>
                    <p id="prevFecFac">Fecha: —</p>
                    <p id="prevPtoFac">Punto: —</p>
                </div>
            </div>

            <div class="prev-sep"></div>

            {{-- Cliente --}}
            <div class="prev-seccion">
                <p class="prev-seccion-titulo">FACTURAR A</p>
                <div class="prev-cliente-grid">
                    <div>
                        <p class="prev-dato-label">Nombre / Razón social</p>
                        <p class="prev-dato-valor" id="prevRazonSocial">—</p>
                    </div>
                    <div>
                        <p class="prev-dato-label">NIT / CI</p>
                        <p class="prev-dato-valor" id="prevNit">—</p>
                    </div>
                </div>
            </div>

            <div class="prev-sep"></div>

            {{-- Tabla de ítems --}}
            <table class="prev-tabla">
                <thead>
                    <tr>
                        <th class="col-concepto-p">Concepto</th>
                        <th style="text-align:center;">Cant.</th>
                        <th style="text-align:right;">P. Unit.</th>
                        <th style="text-align:right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody id="prevItems"></tbody>
                <tfoot>
                    <tr id="prevFilaDesc" style="display:none;">
                        <td colspan="3" style="text-align:right;color:#e57373;padding:6px 10px;">Descuento</td>
                        <td style="text-align:right;color:#e57373;padding:6px 10px;" id="prevDescMonto">—</td>
                    </tr>
                    <tr class="prev-total-row">
                        <td colspan="3" style="text-align:right;font-weight:700;padding:10px;">TOTAL</td>
                        <td style="text-align:right;font-weight:800;color:#ff7b00;font-size:16px;padding:10px;"
                            id="prevTotal">Bs 0.00</td>
                    </tr>
                </tfoot>
            </table>

            <div class="prev-sep"></div>

            <p class="prev-obs" id="prevObs" style="display:none;"></p>

            <p class="prev-pie">
                Gracias por confiar en Taller Automotriz JHIRE · Este documento es válido como comprobante de pago.
            </p>
        </div>
    </div>
</div>

{{-- ══ ESTILOS ══ --}}
<style>
/* ── Page layout ── */
.fac-page { padding-bottom: 48px; }
.fac-topbar {
    display: flex; justify-content: space-between;
    align-items: center; margin-bottom: 24px;
}
.fac-topbar-left  { display: flex; align-items: center; gap: 16px; }
.fac-topbar-right { display: flex; gap: 10px; }
.btn-volver {
    display: inline-flex; align-items: center; gap: 6px;
    color: #aaa; text-decoration: none; font-size: 13px;
    padding: 7px 14px; border: 1px solid #555; border-radius: 6px;
    transition: all 0.2s;
}
.btn-volver:hover { color: #fff; border-color: #888; }
.fac-titulo-wrap  { display: flex; align-items: center; gap: 12px; }
.fac-titulo-icono { font-size: 28px; color: #ff7b00; }
.fac-titulo       { margin: 0; font-size: 20px; color: #ff7b00; font-weight: 700; line-height: 1.2; }
.fac-subtitulo    { margin: 2px 0 0; font-size: 13px; color: #888; display: flex; align-items: center; gap: 7px; }
.fac-cod-badge {
    background: rgba(255,123,0,0.15); color: #ff7b00;
    border: 1px solid rgba(255,123,0,0.35); border-radius: 6px;
    padding: 1px 8px; font-size: 12px; font-weight: 700;
}
.btn-preview {
    background: transparent; color: #17a2b8; border: 1px solid #17a2b8;
    padding: 8px 18px; border-radius: 6px; cursor: pointer;
    font-size: 13px; font-weight: 600; display: inline-flex;
    align-items: center; gap: 6px; transition: all 0.2s;
}
.btn-preview:hover { background: #17a2b8; color: #fff; }

/* ── Columnas ── */
.fac-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
    align-items: start;
}
@media (max-width: 900px) {
    .fac-layout { grid-template-columns: 1fr; }
    .fac-col-side { order: -1; }
}

/* ── Cards ── */
.fac-card {
    background: #2a2a2a;
    border: 1px solid #3a3a3a;
    border-radius: 10px;
    margin-bottom: 16px;
    overflow: hidden;
}
.fac-card:last-child { margin-bottom: 0; }
.fac-card-header {
    display: flex; align-items: center; gap: 8px;
    padding: 12px 18px;
    background: #242424;
    border-bottom: 1px solid #3a3a3a;
    color: #ccc; font-size: 13px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.5px;
}
.fac-card-icono { font-size: 17px; color: #ff7b00; }
.fac-card-body  { padding: 18px; }

/* ── Grids de campos ── */
.fac-grid-3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; }
.fac-grid-2 { display: grid; grid-template-columns: repeat(2,1fr); gap: 14px; }
.fac-campo  { display: flex; flex-direction: column; gap: 5px; }
.fac-campo label { color: #ccc; font-size: 12px; font-weight: 500; }
.fac-campo input {
    width: 100%; padding: 8px 12px;
    background: #3a3a3a; border: 1px solid #555;
    border-radius: 6px; color: #fff; font-size: 14px;
    box-sizing: border-box; transition: border-color 0.2s;
}
.fac-campo input:focus { outline: none; border-color: #ff7b00; }
.req { color: #dc3545; }

/* ── Info cliente ── */
.fac-cliente-info { display: flex; align-items: center; gap: 14px; }
.fac-avatar { font-size: 44px; color: #555; }
.fac-cliente-nombre { margin: 0; color: #fff; font-size: 16px; font-weight: 600; }
.fac-cliente-sub { margin: 4px 0 0; color: #888; font-size: 13px; display: flex; align-items: center; gap: 4px; }

/* ── Tabla de ítems ── */
.fac-items-header {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 10px;
    background: #1e1e1e; border-radius: 6px 6px 0 0;
    font-size: 11px; color: #888; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.5px;
    margin-bottom: 2px;
}
.fac-item-fila {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 4px; border-bottom: 1px solid #2e2e2e;
}
.fac-item-fila:last-child { border-bottom: none; }
.col-concepto { flex: 2; display: flex; flex-direction: column; gap: 3px; }
.col-cant     { width: 80px; }
.col-precio   { width: 120px; }
.col-sub      { width: 120px; text-align: right; }
.col-accion   { width: 36px; text-align: center; }
.fac-input {
    width: 100%; padding: 7px 10px;
    background: #3a3a3a; border: 1px solid #555;
    border-radius: 6px; color: #fff; font-size: 13px;
    box-sizing: border-box; transition: border-color 0.2s;
}
.fac-input:focus { outline: none; border-color: #ff7b00; }
.cant-input   { text-align: center; }
.precio-input { text-align: right; }
.fac-subtotal { color: #4caf50; font-size: 13px; font-weight: 700; }
.fac-badge-origen {
    display: inline-block; font-size: 10px; font-weight: 700;
    padding: 1px 7px; border-radius: 6px; letter-spacing: 0.3px;
}
.fac-badge-origen.srv { background: rgba(23,162,184,0.15); color: #17a2b8; border: 1px solid rgba(23,162,184,0.3); }
.fac-badge-origen.rep { background: rgba(156,111,228,0.15); color: #9c6fe4; border: 1px solid rgba(156,111,228,0.3); }
.btn-quitar-item {
    background: transparent; border: none;
    color: #dc3545; cursor: pointer; padding: 4px;
    transition: color 0.2s;
}
.btn-quitar-item:hover { color: #ff4444; }
.btn-agregar-item-fac {
    display: inline-flex; align-items: center; gap: 6px;
    background: transparent; color: #ff7b00;
    border: 1px dashed #ff7b00;
    padding: 6px 14px; border-radius: 6px; cursor: pointer;
    font-size: 12px; font-weight: 600; margin-top: 10px;
    transition: all 0.2s;
}
.btn-agregar-item-fac:hover { background: rgba(255,123,0,0.1); }

/* ── Textarea ── */
.fac-textarea {
    width: 100%; padding: 9px 12px;
    background: #3a3a3a; border: 1px solid #555;
    border-radius: 6px; color: #fff; font-size: 14px;
    box-sizing: border-box; resize: vertical;
    transition: border-color 0.2s;
}
.fac-textarea:focus { outline: none; border-color: #ff7b00; }

/* ── Columna lateral: resumen ── */
.fac-resumen-fila {
    display: flex; justify-content: space-between;
    padding: 6px 0; color: #aaa; font-size: 14px;
}
.fac-resumen-sep { height: 1px; background: #3a3a3a; margin: 10px 0; }
.fac-resumen-total {
    display: flex; justify-content: space-between;
    align-items: center; font-size: 15px; font-weight: 600; color: #fff;
}
.fac-total-num { font-size: 22px; font-weight: 800; color: #ff7b00; }
.fac-label-desc { color: #aaa; font-size: 12px; display: block; margin-bottom: 5px; }
.fac-input-desc {
    flex: 1; padding: 7px 10px; background: #3a3a3a;
    border: 1px solid #555; border-radius: 6px;
    color: #fff; font-size: 13px; transition: border-color 0.2s;
}
.fac-input-desc:focus { outline: none; border-color: #ff7b00; }

/* ── Columna lateral: info mantenimiento ── */
.fac-info-fila {
    display: flex; justify-content: space-between;
    padding: 6px 0; border-bottom: 1px solid #2e2e2e;
    font-size: 13px;
}
.fac-info-fila:last-child { border-bottom: none; }
.fac-info-label { color: #777; }
.fac-info-valor { color: #ddd; font-weight: 500; }

/* ── Botones de acción ── */
.fac-acciones { display: flex; flex-direction: column; gap: 10px; }
.btn-emitir {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    background: #ff7b00; color: #fff; border: none;
    padding: 13px; border-radius: 8px; cursor: pointer;
    font-size: 15px; font-weight: 700; letter-spacing: 0.3px;
    transition: all 0.2s; width: 100%;
}
.btn-emitir:hover { background: #e06a00; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(255,123,0,0.3); }
.btn-cancelar-fac {
    display: block; text-align: center;
    background: #3a3a3a; color: #aaa;
    padding: 10px; border-radius: 8px;
    text-decoration: none; font-size: 14px;
    transition: all 0.2s;
}
.btn-cancelar-fac:hover { background: #444; color: #fff; }

/* ── Modal vista previa ── */
.preview-box {
    background: #1e1e1e; border: 2px solid #3a3a3a;
    border-radius: 12px; width: 680px; max-width: 96vw;
    max-height: 92vh; overflow-y: auto;
}
.preview-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 24px; border-bottom: 1px solid #3a3a3a;
}
.preview-body { padding: 28px 32px; }
.btn-imprimir-prev {
    display: inline-flex; align-items: center; gap: 5px;
    background: #3a3a3a; color: #ccc; border: 1px solid #555;
    padding: 6px 14px; border-radius: 6px; cursor: pointer;
    font-size: 12px; font-weight: 600; transition: all 0.2s;
}
.btn-imprimir-prev:hover { background: #444; color: #fff; }

/* Vista previa: empresa */
.prev-empresa {
    display: flex; justify-content: space-between;
    align-items: flex-start; margin-bottom: 20px;
}
.prev-empresa-logo { display: flex; align-items: center; gap: 12px; }
.prev-empresa-nombre { margin: 0; font-size: 18px; font-weight: 800; color: #fff; }
.prev-empresa-sub    { margin: 2px 0 0; font-size: 12px; color: #888; }
.prev-fac-meta { text-align: right; }
.prev-fac-meta p { margin: 2px 0; font-size: 13px; color: #ccc; }
.prev-fac-meta strong { color: #ff7b00; font-size: 15px; }
.prev-sep { height: 1px; background: #3a3a3a; margin: 16px 0; }

/* Vista previa: cliente */
.prev-seccion-titulo {
    font-size: 11px; font-weight: 700; color: #888;
    letter-spacing: 0.8px; margin: 0 0 10px;
}
.prev-cliente-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.prev-dato-label { font-size: 11px; color: #777; margin: 0 0 2px; }
.prev-dato-valor { font-size: 14px; color: #fff; font-weight: 600; margin: 0; }

/* Vista previa: tabla */
.prev-tabla { width: 100%; border-collapse: collapse; }
.prev-tabla thead th {
    padding: 8px 10px; background: #2a2a2a;
    color: #888; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.5px;
    border-bottom: 1px solid #3a3a3a;
}
.prev-tabla tbody td {
    padding: 9px 10px; color: #ccc; font-size: 13px;
    border-bottom: 1px solid #2a2a2a;
}
.prev-tabla tbody tr:hover td { background: #252525; }
.col-concepto-p { text-align: left; }
.prev-total-row td { border-top: 2px solid #3a3a3a !important; }

/* Vista previa: pie */
.prev-obs { color: #999; font-size: 12px; font-style: italic; margin: 0 0 12px; }
.prev-pie {
    text-align: center; color: #666; font-size: 11px;
    padding-top: 12px; border-top: 1px dashed #3a3a3a;
}

/* ── Print ── */
@media print {
    body *                      { visibility: hidden; }
    #prevContenido,
    #prevContenido *            { visibility: visible; }
    #prevContenido              { position: fixed; top: 30px; left: 40px; right: 40px; }
    .preview-box, .preview-body { background: #fff !important; }
    .prev-empresa-nombre,
    .prev-fac-meta strong       { color: #000 !important; }
    .prev-dato-valor,
    .prev-empresa-sub,
    .prev-fac-meta p            { color: #333 !important; }
    .prev-tabla thead th        { background: #f0f0f0 !important; color: #333 !important; }
    .prev-tabla tbody td        { color: #333 !important; border-color: #ddd !important; }
    .prev-sep                   { background: #ccc !important; }
    .prev-total-row td          { border-top-color: #999 !important; }
}
</style>

{{-- ══ JAVASCRIPT ══ --}}
<script>
/* ── Estado de ítems ── */
let itemIdx = {{ $serviciosMant->count() + $repuestosMant->count() }};

/* ── Recalcular subtotal de una fila ── */
function recalcular(input) {
    const fila    = input.closest('.fac-item-fila');
    const cant    = parseFloat(fila.querySelector('.cant-input').value)  || 0;
    const precio  = parseFloat(fila.querySelector('.precio-input').value) || 0;
    const sub     = cant * precio;
    fila.querySelector('.fac-subtotal').textContent = 'Bs ' + sub.toFixed(2);
    recalcularTotal();
}

/* ── Recalcular total general ── */
function recalcularTotal() {
    let subtotal = 0;
    document.querySelectorAll('.fac-item-fila').forEach(fila => {
        const cant   = parseFloat(fila.querySelector('.cant-input')?.value)  || 0;
        const precio = parseFloat(fila.querySelector('.precio-input')?.value) || 0;
        subtotal += cant * precio;
    });

    const descPct = parseFloat(document.getElementById('inputDescuento').value) || 0;
    const descMonto  = subtotal * descPct / 100;
    const total   = subtotal - descMonto;

    document.getElementById('resSubtotal').textContent = 'Bs ' + subtotal.toFixed(2);
    document.getElementById('totalHidden').value       = total.toFixed(2);
    document.getElementById('resTotal').textContent    = 'Bs ' + total.toFixed(2);

    const filaDesc = document.getElementById('filaDescuento');
    if (descPct > 0) {
        filaDesc.style.display = 'flex';
        document.getElementById('resDescuento').textContent = '– Bs ' + descMonto.toFixed(2);
    } else {
        filaDesc.style.display = 'none';
    }
}

/* ── Agregar ítem manual ── */
function agregarItemManual() {
    const lista = document.getElementById('listaItems');
    const div   = document.createElement('div');
    div.className  = 'fac-item-fila';
    div.dataset.idx = itemIdx;
    div.innerHTML   = `
        <div class="col-concepto">
            <input type="text"
                   name="items[${itemIdx}][concepto]"
                   class="fac-input concepto-input"
                   placeholder="Descripción del concepto"
                   required>
            <span class="fac-badge-origen" style="background:rgba(255,193,7,0.15);color:#ffc107;border:1px solid rgba(255,193,7,0.3);">Manual</span>
        </div>
        <div class="col-cant">
            <input type="number"
                   name="items[${itemIdx}][cantidad]"
                   class="fac-input cant-input"
                   value="1" min="1" step="1"
                   oninput="recalcular(this)">
        </div>
        <div class="col-precio">
            <input type="number"
                   name="items[${itemIdx}][precio]"
                   class="fac-input precio-input"
                   value="0" min="0" step="0.01"
                   oninput="recalcular(this)">
        </div>
        <div class="col-sub">
            <span class="fac-subtotal">Bs 0.00</span>
        </div>
        <div class="col-accion">
            <button type="button" class="btn-quitar-item" onclick="quitarItem(this)">
                <span class="material-symbols-outlined" style="font-size:18px;">remove_circle</span>
            </button>
        </div>`;
    lista.appendChild(div);
    itemIdx++;
    recalcularTotal();
}

/* ── Quitar ítem ── */
function quitarItem(btn) {
    btn.closest('.fac-item-fila').remove();
    recalcularTotal();
}

/* ── Vista previa ── */
function togglePreview() {
    const modal = document.getElementById('modalPreview');
    if (modal.style.display === 'flex') {
        modal.style.display = 'none';
        return;
    }
    actualizarPreview();
    modal.style.display = 'flex';
}

function actualizarPreview() {
    // Datos cabecera
    document.getElementById('prevNroFac').textContent   = 'Nº ' + (document.querySelector('[name="nfa_fac"]').value || '—');
    document.getElementById('prevFecFac').textContent   = 'Fecha: ' + (document.querySelector('[name="fec_fac"]').value || '—');
    document.getElementById('prevPtoFac').textContent   = 'Punto: ' + (document.querySelector('[name="pto_fac"]').value || '—');
    document.getElementById('prevRazonSocial').textContent = document.querySelector('[name="razon_social"]').value || '—';
    document.getElementById('prevNit').textContent      = document.querySelector('[name="nit_cli"]').value || '—';

    // Ítems
    const tbody = document.getElementById('prevItems');
    tbody.innerHTML = '';
    let subtotal = 0;
    document.querySelectorAll('.fac-item-fila').forEach(fila => {
        const concepto = fila.querySelector('.concepto-input').value || '—';
        const cant     = parseFloat(fila.querySelector('.cant-input').value) || 0;
        const precio   = parseFloat(fila.querySelector('.precio-input').value) || 0;
        const sub      = cant * precio;
        subtotal += sub;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="col-concepto-p">${concepto}</td>
            <td style="text-align:center;">${cant}</td>
            <td style="text-align:right;">Bs ${precio.toFixed(2)}</td>
            <td style="text-align:right;">Bs ${sub.toFixed(2)}</td>`;
        tbody.appendChild(tr);
    });

    // Total con descuento
    const descPct   = parseFloat(document.getElementById('inputDescuento').value) || 0;
    const descMonto = subtotal * descPct / 100;
    const total     = subtotal - descMonto;

    const filaDesc = document.getElementById('prevFilaDesc');
    if (descPct > 0) {
        filaDesc.style.display = '';
        document.getElementById('prevDescMonto').textContent = '– Bs ' + descMonto.toFixed(2);
    } else {
        filaDesc.style.display = 'none';
    }
    document.getElementById('prevTotal').textContent = 'Bs ' + total.toFixed(2);

    // Observaciones
    const obs     = document.querySelector('[name="obs_fac"]').value.trim();
    const obsEl   = document.getElementById('prevObs');
    if (obs) { obsEl.textContent = obs; obsEl.style.display = ''; }
    else      { obsEl.style.display = 'none'; }
}

function imprimirPreview() {
    actualizarPreview();
    window.print();
}

/* ── Cerrar preview al click fuera ── */
window.addEventListener('click', e => {
    const modal = document.getElementById('modalPreview');
    if (e.target === modal) modal.style.display = 'none';
});

/* ── Init: calcular totales al cargar ── */
document.addEventListener('DOMContentLoaded', () => {
    // Calcular subtotales de filas ya cargadas
    document.querySelectorAll('.fac-item-fila').forEach(fila => {
        const cant   = parseFloat(fila.querySelector('.cant-input')?.value)  || 0;
        const precio = parseFloat(fila.querySelector('.precio-input')?.value) || 0;
        const subEl  = fila.querySelector('.fac-subtotal');
        if (subEl) subEl.textContent = 'Bs ' + (cant * precio).toFixed(2);
    });
    recalcularTotal();
});

/* ── Reindexar ítems antes de submit para evitar gaps ── */
document.getElementById('formFactura').addEventListener('submit', function() {
    document.querySelectorAll('#listaItems .fac-item-fila').forEach((fila, i) => {
        fila.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace(/items\[\d+\]/, `items[${i}]`);
        });
    });
});
</script>

@endsection