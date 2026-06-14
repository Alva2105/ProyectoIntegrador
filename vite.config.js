import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/js/solicitud.js',
                'resources/js/solicitud/detalleSolicitud.js',
                'resources/js/dashboard/buscador.js',
                'resources/js/dashboard/buscadorMantenimientos.js',
                'resources/js/dashboard/buscadorVehiculos.js',
                'resources/js/inventario/abrirDetalles.js',
                'resources/js/inventario/cantRepuesto.js',
                'resources/js/inventario/grafico.js',
                'resources/js/reportes/buscadorClientes.js',
                'resources/js/reportes/buscadorTecnicos.js',
                'resources/css/solicitud.css',
                'resources/css/detalleSolicitud.css',
                'resources/css/detallesReportes.css',
                'resources/css/detallesRepuestos.css',
                'resources/css/grafico.css',
                'resources/css/inventarioTabla.css',
                'resources/css/listadoSolicitudes.css',
                'resources/css/reportesTecnicos.css',
                'resources/css/tablon-seguimiento.css',
            ],
            refresh: true,
        }),
    ],
});
