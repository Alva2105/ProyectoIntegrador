<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>JHIRE MOTORS | Dashboard</title>
    <link rel="icon" type="image/png" href="{{ asset('favicons/jhire.ico') }}">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
 
<body class="dashboard-body">
    <!-- ===== Sidebar ===== -->
    <aside class="dashboard-sidebar">
        <div class="dashboard-logo">
            <img src="{{ asset('assets/img/logos/jhire.png') }}" alt="JHIRE">
        </div>
 
        <nav class="dashboard-menu">
            <ul>
                <!-- === Usuarios con submenú === -->
                <li class="has-submenu {{ request()->is('dashboard/usuarios*') ? 'open' : '' }}">
                    <button class="submenu-toggle {{ request()->is('dashboard/usuarios*') ? 'active' : '' }}"
                            data-route="{{ route('dashboard.usuarios') }}">
                        <i class="material-symbols-outlined">groups</i> Usuarios
                        <i class="arrow material-symbols-outlined">expand_more</i>
                    </button>
                    <ul class="submenu">
                        <li>
                            <a href="{{ route('usuarios.clientes') }}"
                               class="{{ request()->is('dashboard/usuarios/clientes') ? 'active-sub' : '' }}">
                                <i class="material-symbols-outlined tiny">person</i> Clientes
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('usuarios.tecnicos') }}"
                               class="{{ request()->is('dashboard/usuarios/tecnicos') ? 'active-sub' : '' }}">
                                <i class="material-symbols-outlined tiny">engineering</i> Técnicos Automotrices
                            </a>
                        </li>
                    </ul>
                </li>
 
                <!-- === Vehículos === -->
                <li>
                    <a href="{{ route('dashboard.vehiculos') }}"
                       class="{{ request()->routeIs('dashboard.vehiculos') ? 'active' : '' }}">
                        <i class="material-symbols-outlined">directions_car</i> Vehículos
                    </a>
                </li>
 
                <!-- === Mantenimientos con submenú === -->
                <li class="has-submenu {{ request()->is('dashboard/mantenimientos*') ? 'open' : '' }}">
                    <button class="submenu-toggle {{ request()->is('dashboard/mantenimientos*') ? 'active' : '' }}"
                            data-route="{{ route('dashboard.mantenimientos') }}">
                        <i class="material-symbols-outlined">car_gear</i> Mantenimientos
                        <i class="arrow material-symbols-outlined">expand_more</i>
                    </button>
                    <ul class="submenu">
                        <li>
                            <a href="{{ route('mantenimientos.correctivos') }}"
                               class="{{ request()->is('dashboard/mantenimientos/correctivos') ? 'active-sub' : '' }}">
                                <i class="material-symbols-outlined tiny">service_toolbox</i> Correctivos
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('mantenimientos.preventivos') }}"
                               class="{{ request()->is('dashboard/mantenimientos/preventivos') ? 'active-sub' : '' }}">
                                <i class="material-symbols-outlined tiny">home_repair_service</i> Preventivos
                            </a>
                        </li>
                    </ul>
                </li>
 
                {{-- ════════════════════════════════════════
                     NUEVO: Seguimientos (ítem directo, sin submenú)
                ════════════════════════════════════════ --}}
                <li>
                    <a href="{{ route('dashboard.seguimientos') }}"
                       class="{{ request()->routeIs('dashboard.seguimientos') || request()->routeIs('seguimientos.reporte') ? 'active' : '' }}">
                        <i class="material-symbols-outlined">sticky_note_2</i> Seguimientos
                    </a>
                </li>
 
                <!-- === Solicitudes con submenú === -->
                <li class="has-submenu">
                    <button class="submenu-toggle">
                        <i class="material-symbols-outlined">assignment</i> Solicitudes
                        <i class="arrow material-symbols-outlined">expand_more</i>
                    </button>
                    <ul class="submenu">
                        <li>
                            <a href="{{ route('usuarios.clientes') }}">
                                <i class="material-symbols-outlined tiny">assignment_turned_in</i> Completas
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('usuarios.tecnicos') }}">
                                <i class="material-symbols-outlined tiny">pending_actions</i> En Proceso
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
 
        <!-- ===== Opciones del usuario ===== -->
        <div class="dashboard-user-actions">
            <a href="{{ route('dashboard.perfil') }}"
               class="dashboard-link user-link {{ request()->is('dashboard/perfil') ? 'active' : '' }}">
                <i class="material-symbols-outlined">account_circle</i>
                Mi Perfil
            </a>
            <a href="#"
               class="dashboard-link logout-link"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="material-symbols-outlined">logout</i>
                Cerrar Sesión
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                @csrf
            </form>
        </div>
    </aside>
 
    <!-- ===== Contenido principal ===== -->
    <main class="dashboard-content">
        @yield('contenido')
    </main>
 
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggles    = document.querySelectorAll('.submenu-toggle');
        const currentPath = window.location.pathname;
 
        toggles.forEach(toggle => {
            const li = toggle.closest('.has-submenu');
            const targetRoute = toggle.dataset.route;
 
            toggle.addEventListener('click', function () {
                if (!targetRoute) return;
                const targetPath = new URL(targetRoute, window.location.origin).pathname;
                const inSameSection = currentPath.startsWith(targetPath);
                const isMainView    = currentPath === targetPath;
 
                if (!inSameSection) {
                    localStorage.setItem('closeSubmenu', targetPath);
                    window.location.href = targetRoute;
                    return;
                }
                if (isMainView) {
                    li.classList.toggle('js-closed');
                    li.classList.toggle('open');
                    return;
                }
                localStorage.setItem('closeSubmenu', targetPath);
                window.location.href = targetRoute;
            });
        });
 
        const toClose    = localStorage.getItem('closeSubmenu');
        const hasVisited = localStorage.getItem('hasVisitedDashboard');
        const cameFromLogin = document.referrer.includes('/login');
 
        document.querySelectorAll('.has-submenu').forEach(li => {
            if (cameFromLogin || !hasVisited) {
                li.classList.add('js-closed');
                li.classList.remove('open');
            } else if (toClose && location.pathname.startsWith(toClose)) {
                li.classList.add('js-closed');
                li.classList.remove('open');
            }
        });
 
        document.querySelectorAll('.has-submenu').forEach(li => {
            const btn = li.querySelector('.submenu-toggle');
            if (!btn || !btn.dataset.route) return;
            const targetPath = new URL(btn.dataset.route, window.location.origin).pathname;
            if (currentPath.startsWith(targetPath) && currentPath !== targetPath) {
                li.classList.remove('js-closed');
                li.classList.add('open');
            }
        });
 
        localStorage.removeItem('closeSubmenu');
        localStorage.setItem('hasVisitedDashboard', 'true');
    });
    </script>
 
    @stack('scripts')
</body>
</html>