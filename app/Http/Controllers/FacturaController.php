<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Factura;
use App\Models\DetalleFactura;
use App\Models\Mantenimiento;

class FacturaController extends Controller
{
    public function listar(Request $request)
    {
        $query = Factura::with([
                'cliente',
                'detalles',
                'mantenimiento.solicitud.vehiculo',
            ])
            ->orderBy('cod_facturas', 'desc');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('nfa_fac', 'ILIKE', "%$q%")
                    ->orWhereHas('cliente', function ($c) use ($q) {
                        $c->where('nom_cli', 'ILIKE', "%$q%")
                        ->orWhere('app_cli', 'ILIKE', "%$q%")
                        ->orWhere('cod_clientes', 'ILIKE', "%$q%");
                    });
            });
        }

        $facturas = $query->paginate(10)->withQueryString();

        return view('admin.dashboardFacturas', compact('facturas'));
    }

    public function crear($cod)
    {
        $mantenimiento = Mantenimiento::with([
            'solicitud.cliente',
            'solicitud.vehiculo',
            'servicios',
            'repuestos' => function($q) { $q->withPivot('cantidad', 'pre_uni'); },
        ])->findOrFail($cod);

        if ($mantenimiento->factura) {
            return redirect()
                ->route('facturas.ver', $mantenimiento->factura->cod_facturas)
                ->with('info', 'Este mantenimiento ya tiene una factura emitida.');
        }

        $ultimo     = Factura::orderBy('cod_facturas', 'desc')->first();
        $numero     = $ultimo ? ((int) ltrim($ultimo->nfa_fac, '0') + 1) : 1;
        $siguienteNfa = str_pad($numero, 6, '0', STR_PAD_LEFT);

        return view('facturas.crear', compact('mantenimiento', 'siguienteNfa'));
    }

    public function guardar(Request $request, $cod)
    {
        $mantenimiento = Mantenimiento::with('solicitud.cliente')->findOrFail($cod);

        if ($mantenimiento->factura) {
            return redirect()
                ->route('facturas.ver', $mantenimiento->factura->cod_facturas)
                ->with('info', 'Este mantenimiento ya tiene una factura emitida.');
        }

        $request->validate([
            'nfa_fac'               => 'required|string|max:30|unique:facturas,nfa_fac',
            'fec_fac'               => 'required|date',
            'pto_fac'               => 'required|string|max:10',
            'cod_clientes_fac'      => 'nullable|exists:clientes,cod_clientes',
            'items'                 => 'required|array|min:1',
            'items.*.concepto'      => 'required|string|max:200',
            'items.*.cantidad'      => 'required|integer|min:1',
            'items.*.precio'        => 'required|numeric|min:0',
            'items.*.cod_repuesto'  => 'nullable|string|exists:repuestos,cod_repuestos',
        ], [
            'nfa_fac.unique'            => 'El número de factura ya existe.',
            'items.required'            => 'Debe haber al menos un ítem en la factura.',
            'items.*.concepto.required' => 'Todos los ítems deben tener un concepto.',
        ]);

        $factura = DB::transaction(function () use ($request, $mantenimiento) {

            $ultimo = Factura::orderBy('cod_facturas', 'desc')->first();
            $numero = $ultimo ? ((int) ltrim($ultimo->nfa_fac, '0') + 1) : 1;
            $nfa    = str_pad($numero, 6, '0', STR_PAD_LEFT); // 000001, 000002...

            $factura = Factura::create([
                'fec_fac'          => $request->fec_fac,
                'nfa_fac'          => $nfa,
                'pto_fac'          => $request->pto_fac,
                'cod_clientes_fac' => $request->cod_clientes_fac,
            ]);

            foreach ($request->items as $item) {
                DetalleFactura::create([
                    'con_det'           => $item['concepto'],
                    'can_det'           => $item['cantidad'],
                    'pun_det'           => $item['precio'],
                    'cod_facturas_det'  => $factura->cod_facturas,
                    'cod_repuestos_det' => $item['cod_repuesto'] ?? null,
                ]);
            }

            $mantenimiento->update([
                'cod_facturas_man' => $factura->cod_facturas,
            ]);

            return $factura;
        });

        return redirect()
            ->route('facturas.ver', $factura->cod_facturas)
            ->with('success', 'Factura #' . $factura->nfa_fac . ' emitida correctamente.');
    }

    public function ver($cod)
    {
        $factura = Factura::with([
            'cliente',
            'detalles',
            'detalles.repuesto',
            'mantenimiento.solicitud.vehiculo',
        ])->findOrFail($cod);

        return view('facturas.ver', compact('factura'));
    }

    public function reporte(Request $request)
    {
        $query = Factura::with([
            'cliente',
            'detalles',
            'mantenimiento.solicitud.vehiculo',
        ]);

        if ($request->filled('desde')) {
            $query->whereDate('fec_fac', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fec_fac', '<=', $request->hasta);
        }
        if ($request->filled('cliente')) {
            $query->whereHas('cliente', fn($q) =>
                $q->where('nom_cli', 'ILIKE', '%'.$request->cliente.'%')
                ->orWhere('app_cli', 'ILIKE', '%'.$request->cliente.'%')
            );
        }
        if ($request->filled('nfa')) {
            $query->where('nfa_fac', 'ILIKE', '%'.$request->nfa.'%');
        }

        $facturas = $query->orderBy('fec_fac', 'desc')->get();

        // Datos para gráfico de barras: ingresos por mes
        $ingresosPorMes = $facturas->groupBy(fn($f) =>
            \Carbon\Carbon::parse($f->fec_fac)->format('Y-m')
        )->map(fn($grupo) =>
            $grupo->sum(fn($f) => $f->detalles->sum(fn($d) => $d->can_det * $d->pun_det))
        );

        // Datos para gráfico dona: top conceptos facturados
        $topConceptos = DetalleFactura::selectRaw('con_det, SUM(can_det * pun_det) as total')
            ->groupBy('con_det')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return view('admin.reporteFacturas', compact('facturas', 'ingresosPorMes', 'topConceptos'));
    }

}