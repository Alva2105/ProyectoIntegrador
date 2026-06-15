<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Factura;
use App\Models\DetalleFactura;
use App\Models\Mantenimiento;

class FacturaController extends Controller
{
    /**
     * Muestra el formulario para crear la factura de un mantenimiento.
     * Ruta: GET /dashboard/mantenimientos/{cod}/facturar
     */
    public function crear($cod)
    {
        $mantenimiento = Mantenimiento::with([
            'solicitud.cliente',
            'solicitud.vehiculo',
            'servicios',   // relación many-to-many con tabla pivot
            'repuestos',   // relación many-to-many con tabla pivot
        ])->findOrFail($cod);

        // Bloquear si ya tiene factura
        if ($mantenimiento->factura) {
            return redirect()
                ->route('mantenimientos.index')
                ->with('error', 'Este mantenimiento ya tiene una factura emitida (#'
                    . $mantenimiento->factura->nfa_fac . ').');
        }

        return view('facturas.crear', compact('mantenimiento'));
    }

    /**
     * Persiste la factura y sus detalles.
     * Ruta: POST /dashboard/mantenimientos/{cod}/facturar
     */
    public function guardar(Request $request, $cod)
    {
        $mantenimiento = Mantenimiento::with('solicitud.cliente')->findOrFail($cod);

        // Doble-check: no permitir duplicados
        if ($mantenimiento->factura) {
            return back()->with('error', 'Este mantenimiento ya tiene una factura emitida.');
        }

        // ── Validación ──────────────────────────────────────────────────────────
        $request->validate([
            'nfa_fac'          => 'required|string|max:30|unique:facturas,nfa_fac',
            'fec_fac'          => 'required|date',
            'pto_fac'          => 'required|string|max:10',
            'nit_cli'          => 'required|string|max:20',
            'razon_social'     => 'nullable|string|max:150',
            'cod_clientes_fac' => 'nullable|exists:clientes,cod_clientes',
            'descuento_pct'    => 'nullable|numeric|min:0|max:100',
            'total_fac'        => 'required|numeric|min:0',
            'obs_fac'          => 'nullable|string|max:500',
            'items'            => 'required|array|min:1',
            'items.*.concepto' => 'required|string|max:200',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio'   => 'required|numeric|min:0',
        ], [
            'nfa_fac.unique'        => 'El número de factura ya existe.',
            'items.required'        => 'Debe haber al menos un ítem en la factura.',
            'items.*.concepto.required' => 'Todos los ítems deben tener un concepto.',
        ]);

        // ── Crear factura ────────────────────────────────────────────────────────
        $factura = Factura::create([
            'fec_fac'          => $request->fec_fac,
            'nfa_fac'          => $request->nfa_fac,
            'pto_fac'          => $request->pto_fac,
            'cod_clientes_fac' => $request->cod_clientes_fac,
            // cod_entregas_fac queda null porque se factura directo desde mantenimiento
        ]);

        // ── Crear detalles ───────────────────────────────────────────────────────
        foreach ($request->items as $item) {
            DetalleFactura::create([
                'con_det'          => $item['concepto'],
                'can_det'          => $item['cantidad'],
                'pun_det'          => $item['precio'],
                'cod_facturas_det' => $factura->cod_facturas,
                'cod_repuestos_det'=> $item['cod_repuesto'] ?? null,
            ]);
        }

        // ── Vincular factura al mantenimiento ───────────────────────────────────
        // Ajusta el nombre del campo FK según tu tabla mantenimientos
        $mantenimiento->update([
            'cod_facturas_man' => $factura->cod_facturas,
        ]);

        return redirect()
            ->route('facturas.ver', $factura->cod_facturas)
            ->with('success', 'Factura #' . $factura->nfa_fac . ' emitida correctamente.');
    }

    /**
     * Muestra la factura emitida (solo lectura / impresión).
     * Ruta: GET /dashboard/facturas/{cod}
     */
    public function ver($cod)
    {
        $factura = Factura::with([
            'cliente',
            'detalles',
            'detalles.repuesto',
        ])->findOrFail($cod);

        return view('facturas.ver', compact('factura'));
    }
}