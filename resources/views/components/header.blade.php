<header class="header">
    <div class="header-content flex items-center justify-between w-full">   
        <!-- LOGO -->
        <div class="logo">
            <div class="logo-container">
                <a href="{{ route('welcome') }}">
                    <img src="{{ asset('assets/img/logos/jhire.png') }}" alt="JHIRE Logo" class="logo-principal">
                </a>
            </div>
        </div>
        <!-- BOTÓN PARA IR A REGISTRAR VEHÍCULO -->
        <div class="header-botones">
        @auth
            @php
                $esCliente = Auth::user()->cod_rol == 3;
            @endphp
        
            @if ($esCliente)
                <a href="{{ route('vehiculos.registrar') }}" class="btn-registro-vehiculo {{ request()->routeIs('vehiculos.registrar') ? 'activo' : '' }}">
                    <span class="icono-registro-vehiculo material-symbols-outlined">directions_car</span>
                    Registra tu Vehículo
                </a>
            @endif
        @endauth
        </div>
        <!-- ICONO DE PERFIL -->
        <div class="user-icon">
            @auth
                <div class="profile-icon">
                    <div class="dropdown">
                        <button type="button" class="logout-btn" id="userMenuBtn" title="Menú de usuario">
                            @php
                                $usuario = Auth::user();
                                $imagen = $usuario->img_usu
                                    ? asset('storage/' . $usuario->img_usu)
                                    : asset('assets/img/icons/default-user.png');
                            @endphp
                            <img src="{{ $imagen }}" alt="Foto de perfil" class="user-avatar">
                        </button>
                        <div class="dropdown-menu" id="userDropdown">
                            <a href="{{ route('cliente.perfil') }}" class="dropdown-item flex items-center gap-2">
                                {!! file_get_contents(public_path('assets/img/icons/profile-circle.svg')) !!}
                                <span>Mi Perfil</span>
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item btn-logout flex items-center gap-2">
                                    {!! file_get_contents(public_path('assets/img/icons/log-out.svg')) !!}
                                    <span>Cerrar Sesión</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="btn-login-header">
                        <span class="material-symbols-outlined">login</span>
                        Iniciar Sesión
                    </a>
                @endif
            @endauth
        </div>  

    </div>
    <style>
        /* Header */
        .header {
            background:
                linear-gradient(to right, rgba(0, 0, 0, 0.35), transparent 15%, transparent 85%, rgba(0, 0, 0, 0.35)),
                linear-gradient(180deg, var(--orange-2), var(--orange-1) 5%, #e35d00 100%);
            width: 100%;
            height: 140px;
            display: flex;
            align-items: center;
            position: relative;
            z-index: 10;
        }

        .header::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            height: 5px;
            background: white;
            box-shadow:
                0 0 8px rgba(0, 0, 0, 1),
                0 0 15px rgba(0, 0, 0, 1),
                0 0 25px rgba(255, 255, 255, 0.85),
                0 0 50px rgba(0, 0, 0, 0.5);
            filter: blur(0.3px);
            z-index: -1;
        }

        .header-content {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            margin-top: 30px;
            margin-bottom: 30px;
        }

        /*! === LOGO === */
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            position: relative;
        }

        .logo-principal {
            max-height: 190px;
            width: auto;
            object-fit: contain;
        }

        .login-icon {
            width: 60px;
            height: 60px;
            object-fit: contain;
            transition: transform 0.2s;
        }

        /*! === USER ICON === */
        .user-icon {
            position: relative;
            display: inline-block;
            margin-right: 20px;
        }

        .profile-icon {
            width: 75px;
            height: 75px;
            background-color: #ffffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            transition: transform 0.2s ease, background-color 0.3s ease;
        }

        /* Contenedor general */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        /* Botón circular con inicial */
        .logout-btn {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background-color: #ff7a00;
            color: #fff;
            font-weight: bold;
            border: none;
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;     /* para que la imagen no se salga del círculo */
            transition: background 0.3s ease;
        }

        .user-avatar {
            width: 100%;          /* ocupa todo el botón */
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ff6b35;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }     

        .user-avatar:hover {
            box-shadow: 0 0 10px rgba(255, 107, 53, 0.5);
        }

        .logout-btn:hover {
            background-color: #ffb84d;
        }

        /* Menú desplegable */
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 68px;
            background: #111;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            min-width: 160px;
            padding: 6px 0;
            z-index: 20;
        }

        /* Elementos del menú */
        .dropdown-item {
            display: flex;
            color: #fff;
            text-decoration: none;
            padding: 10px 18px;
            font-size: 14px;
            background: transparent;
            transition: background 0.3s ease;
            gap: 8px;
        }

        .dropdown-item.btn-logout {
            appearance: none;
            background: transparent;
            border: none;
            padding: 10px 18px;
            color: #fff;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dropdown-item:hover {
            background: #ff7a00;
            z-index: 21;
        }

        /* Cerrar sesión con color especial */
        .dropdown-item.btn-logout:hover {
            background: #ff4d4d;
        }

        /* Mostrar el menú cuando se active */
        .dropdown.show .dropdown-menu {
            display: block;
        }

        /* ===== BOTÓN TIPO NAV LIFI ===== */
        .btn-registro-vehiculo {
            display: flex;
            align-items: center;
            gap: 8px;       
            color: white;
            font-family: 'Sensational', sans-serif;
            font-weight: 600;
            font-size: 21px;
            text-decoration: none;
            cursor: pointer;        
            border-radius: 10px;
            background: none; /* igual a un nav-link, no botón sólido */        
            position: relative;
            transition: color 0.35s ease, transform 0.35s ease;
        }

        /* Hover tipo nav LiFi */
        .btn-registro-vehiculo:hover {
            color: #000000ff;
            transform: translateY(-3px);
        }       

        /* Línea animada como los enlaces del nav */
        .btn-registro-vehiculo::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: -4px;
            width: 0%;
            height: 2px;
            background-color: #000000ff;
            border-radius: 2px;
            transition: width 0.35s ease;
        }       

        .btn-registro-vehiculo:hover::after {
            width: 100%;
        }       

        /* Ícono material */
        .btn-registro-vehiculo .material-symbols-outlined {
            font-size: 27px;
            display: flex;
            align-items: center;
        }

        .btn-registro-vehiculo.activo {
            color: #000000ff; /* mismo color que hover */
        }
        
        .btn-registro-vehiculo.activo::after {
            width: 100%;
        }

        /* Botón Iniciar Sesión (solo para invitados) */
        .btn-login-header {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            font-family: 'Sansation', sans-serif;
            font-weight: 700;
            font-size: 18px;
            text-decoration: none;
            padding: 10px 22px;
            border: 2px solid #fff;
            border-radius: 8px;
            background: transparent;
            cursor: pointer;
            transition: background 0.3s ease, color 0.3s ease, transform 0.2s ease;
            letter-spacing: 0.5px;
            margin-right: 20px;
        }

        .btn-login-header:hover {
            background: #fff;
            color: #e35d00;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.25);
        }

        .btn-login-header .material-symbols-outlined {
            font-size: 22px;
        }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const userBtn = document.getElementById('userMenuBtn');
        const dropdown = document.querySelector('.dropdown');

        if (userBtn && dropdown) {
            // Alternar menú al hacer clic en el botón
            userBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('show');
            });

            // Cerrar menú al hacer clic fuera
            document.addEventListener('click', () => {
                dropdown.classList.remove('show');
            });
        }
    });
    </script>
</header>