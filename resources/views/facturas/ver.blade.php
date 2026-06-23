@extends('layouts.dashboard')

@section('contenido')

@if(session('success'))
    <div style="background:#28a745;color:#fff;padding:14px 20px;border-radius:8px;margin-bottom:16px;">
        <strong>✓</strong> {{ session('success') }}
    </div>
@endif

@php
    $vehiculo = $factura->mantenimiento?->solicitud?->vehiculo;
    $cliente  = $factura->cliente;
@endphp

<div class="fac-page">

    <div class="fac-topbar">
        <div class="fac-topbar-left">
            <a href="{{ url('/dashboard/facturas') }}" class="btn-volver">
                <span class="material-symbols-outlined" style="font-size:18px;vertical-align:middle;">arrow_back</span>
                Volver
            </a>
            <div class="fac-titulo-wrap">
                <span class="material-symbols-outlined fac-titulo-icono">receipt_long</span>
                <div>
                    <h2 class="fac-titulo">Factura #{{ $factura->nfa_fac }}</h2>
                    <p class="fac-subtitulo">
                        Emitida el {{ \Carbon\Carbon::parse($factura->fec_fac)->format('d/m/Y') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="fac-topbar-right">
            <button type="button" class="btn-preview" onclick="window.print()">
                <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">print</span>
                Imprimir
            </button>
        </div>
    </div>

    {{-- Contenido imprimible --}}
    <div id="facturaImprimible">

        {{-- CABECERA --}}
        <div class="pi-header">
            <div class="pi-empresa">
                <img src="{{ asset('assets/img/logos/Logo JHIRE.png') }}" alt="JHIRE" class="pi-logo">
                <div class="pi-empresa-datos">
                    <p class="pi-empresa-nombre">MULTISERVICIOS AUTOMOTRIZ "JHIRE"</p>
                    <p class="pi-empresa-sub">De: Janeth Blanco Quispe</p>
                    <p class="pi-empresa-sub"><strong>CASA MATRIZ</strong></p>
                    <p class="pi-empresa-sub">Calle 15 Las Palmas Nro. 4008, Zona Llojeta Bajo,</p>
                    <p class="pi-empresa-sub">Telf.: 2 426651 - 699 42715</p>
                    <p class="pi-empresa-sub">La Paz - Bolivia</p>
                </div>
            </div>
            <div class="pi-fac-centro">
                <p class="pi-fac-grande">FACTURA</p>
                <p class="pi-fac-credito">CON DERECHO A CREDITO FISCAL</p>
            </div>
            <div class="pi-fac-derecha">
                <div class="pi-nit-box">
                    <p class="pi-nit-label">NIT: 4883463018</p>
                    <p class="pi-nit-label">CÓDIGO DE AUTORIZACIÓN</p>
                    <p class="pi-nit-label"><strong>1019FB8293A21A</strong></p>
                </div>
                <div class="pi-nro-box">
                    <p>Nº <strong>{{ $factura->nfa_fac }}</strong></p>
                </div>
                <p class="pi-tipo-doc">MANTENIMIENTO Y REPARACIÓN<br>DE VEHÍCULOS AUTOMOTORES</p>
                <p class="pi-original">ORIGINAL</p>
            </div>
        </div>

        {{-- FECHA + NIT --}}
        <div class="pi-fecha-row">
            <div class="pi-lapaz-wrap">
                <div class="pi-lapaz-label">La Paz.</div>
                <div class="pi-fechas">
                    <div class="pi-fecha-col">
                        <span class="pi-fecha-titulo">DIA</span>
                        <div class="pi-fecha-celda">{{ \Carbon\Carbon::parse($factura->fec_fac)->format('d') }}</div>
                    </div>
                    <div class="pi-fecha-col">
                        <span class="pi-fecha-titulo">MES</span>
                        <div class="pi-fecha-celda">{{ \Carbon\Carbon::parse($factura->fec_fac)->format('m') }}</div>
                    </div>
                    <div class="pi-fecha-col">
                        <span class="pi-fecha-titulo">AÑO</span>
                        <div class="pi-fecha-celda pi-fecha-anio">{{ \Carbon\Carbon::parse($factura->fec_fac)->format('Y') }}</div>
                    </div>
                </div>
            </div>
            <div class="pi-nit-cliente">
                NIT/C.I.: <strong>{{ $factura->cod_clientes_fac ?? '—' }}</strong>
            </div>
        </div>

        {{-- SEÑOR(ES) --}}
        <div class="pi-senor-row">
            <span>Señor(es):</span>
            <span class="pi-senor-valor">
                {{ $cliente?->nom_cli }} {{ $cliente?->app_cli }}
            </span>
        </div>

        {{-- TABLA --}}
        <table class="pi-tabla">
            <thead>
                <tr>
                    <th class="pi-th-cant">CANT.</th>
                    <th>CONCEPTO / DETALLE</th>
                    <th class="pi-th-precio">P.UNIT.</th>
                    <th class="pi-th-sub">SUB TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($factura->detalles as $det)
                    <tr>
                        <td style="text-align:center;">{{ $det->can_det }}</td>
                        <td>{{ $det->con_det }}</td>
                        <td style="text-align:right;">Bs {{ number_format($det->pun_det, 2) }}</td>
                        <td style="text-align:right;">Bs {{ number_format($det->can_det * $det->pun_det, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="pi-fila-total">
                    <td colspan="2"></td>
                    <td style="text-align:right;font-weight:700;">Bolivianos.</td>
                    <td style="text-align:right;font-weight:900;background:#000;color:#fff;">
                        TOTAL: Bs {{ number_format($factura->detalles->sum(fn($d) => $d->can_det * $d->pun_det), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        {{-- SON --}}
        <div class="pi-son-row">
            Son: {{ number_format($factura->detalles->sum(fn($d) => $d->can_det * $d->pun_det), 2) }} Bolivianos.
        </div>

    </div>

</div>

<style>
.fac-page { padding-bottom: 48px; }
.fac-topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
.fac-topbar-left { display:flex; align-items:center; gap:16px; }
.fac-topbar-right { display:flex; gap:10px; }
.btn-volver { display:inline-flex; align-items:center; gap:6px; color:#aaa; text-decoration:none; font-size:13px; padding:7px 14px; border:1px solid #555; border-radius:6px; transition:all 0.2s; }
.btn-volver:hover { color:#fff; border-color:#888; }
.fac-titulo-wrap { display:flex; align-items:center; gap:12px; }
.fac-titulo-icono { font-size:28px; color:#ff7b00; }
.fac-titulo { margin:0; font-size:20px; color:#ff7b00; font-weight:700; }
.fac-subtitulo { margin:2px 0 0; font-size:13px; color:#888; }
.btn-preview { background:transparent; color:#17a2b8; border:1px solid #17a2b8; padding:8px 18px; border-radius:6px; cursor:pointer; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px; }
.btn-preview:hover { background:#17a2b8; color:#fff; }

/* Factura */
#facturaImprimible { background:#fff; color:#000; padding:24px 28px; border-radius:8px; max-width:800px; margin:0 auto; font-family:Arial,sans-serif; }
.pi-header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #003087; padding-bottom:10px; margin-bottom:8px; gap:10px; }
.pi-empresa { display:flex; align-items:flex-start; gap:10px; flex:1.2; }
.pi-logo { width:90px; height:auto; object-fit:contain; }
.pi-empresa-nombre { font-size:11pt; font-weight:900; color:#003087; margin:0 0 2px; text-transform:uppercase; }
.pi-empresa-sub { font-size:8pt; color:#000; margin:0; line-height:1.4; }
.pi-fac-centro { flex:1; text-align:center; display:flex; flex-direction:column; align-items:center; justify-content:center; }
.pi-fac-grande { font-size:28pt; font-weight:900; color:#003087; margin:0; letter-spacing:2px; }
.pi-fac-credito { font-size:8pt; font-weight:700; color:#fff; background:#003087; padding:2px 8px; margin:2px 0 0; border-radius:2px; }
.pi-fac-derecha { flex:1; text-align:right; display:flex; flex-direction:column; align-items:flex-end; gap:4px; }
.pi-nit-box { background:#003087; color:#fff; padding:4px 8px; border-radius:3px; text-align:center; }
.pi-nit-label { font-size:8pt; color:#fff; margin:0; line-height:1.4; }
.pi-nro-box { border:2px solid #c0392b; padding:3px 12px; border-radius:3px; color:#c0392b; font-size:13pt; font-weight:900; text-align:center; }
.pi-tipo-doc { font-size:8pt; color:#000; margin:0; text-align:right; line-height:1.3; }
.pi-original { font-size:9pt; font-weight:900; color:#c0392b; margin:0; }
.pi-fecha-row { display:flex; justify-content:space-between; align-items:flex-end; margin:8px 0; border-bottom:1px solid #003087; padding-bottom:4px; }
.pi-lapaz-wrap { display:flex; align-items:flex-end; gap:6px; }
.pi-lapaz-label { font-size:9pt; font-weight:700; writing-mode:vertical-rl; transform:rotate(180deg); border:1px solid #003087; padding:2px 3px; margin-right:4px; color:#003087; }
.pi-fechas { display:flex; gap:4px; align-items:flex-end; }
.pi-fecha-col { display:flex; flex-direction:column; align-items:center; gap:2px; }
.pi-fecha-titulo { font-size:7pt; color:#555; text-transform:uppercase; }
.pi-fecha-celda { border:1px solid #003087; padding:3px 10px; min-width:36px; text-align:center; font-weight:700; font-size:10pt; }
.pi-fecha-anio { min-width:55px; }
.pi-nit-cliente { font-size:10pt; color:#000; border-bottom:1px solid #003087; padding-bottom:2px; min-width:180px; text-align:right; }
.pi-senor-row { display:flex; gap:8px; font-size:10pt; border-bottom:1px solid #003087; padding:3px 0; margin-bottom:6px; }
.pi-senor-valor { font-weight:700; flex:1; }
.pi-tabla { width:100%; border-collapse:collapse; }
.pi-tabla th { border:1px solid #003087; padding:5px 7px; font-size:8pt; text-transform:uppercase; color:#003087; font-weight:900; text-align:center; }
.pi-th-cant { width:55px; }
.pi-th-precio { width:80px; text-align:right; }
.pi-th-sub { width:100px; text-align:right; }
.pi-tabla td { border:1px solid #003087; padding:5px 7px; font-size:10pt; color:#000; }
.pi-fila-total td { border-top:2px solid #000; }
.pi-son-row { font-size:9pt; margin-top:6px; color:#000; border-top:1px solid #003087; padding-top:4px; }

@media print {
    @page { size:A4 portrait; margin:10mm 12mm; }
    body * { visibility:hidden !important; }
    #facturaImprimible, #facturaImprimible * { visibility:visible !important; }
    #facturaImprimible { position:fixed !important; inset:0; padding:10mm 15mm; background:#fff !important; }
    .fac-topbar, .btn-volver, .btn-preview { display:none !important; }
    .pi-fac-credito { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .pi-nit-box { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .pi-fila-total td:last-child { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>

@endsection