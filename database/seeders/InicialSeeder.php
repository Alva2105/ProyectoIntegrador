<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Usuario;
use App\Models\Cliente;

class InicialSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================
        // 1. ROLES (Llave primaria: cod_roles [Integer])
        // =====================================================
        $roles = [
            ['cod_roles' => 1, 'nom_rol' => 'SuperAdmin'],
            ['cod_roles' => 2, 'nom_rol' => 'Gerente'],
            ['cod_roles' => 3, 'nom_rol' => 'TecnicoAutomotriz'],
            ['cod_roles' => 4, 'nom_rol' => 'Cliente'],
        ];

        foreach ($roles as $rol) {
            DB::table('roles')->insertOrIgnore($rol);
        }

        // =====================================================
        // 2. SUPERADMIN (cod_usuarios es VARCHAR)
        // =====================================================
        Usuario::updateOrCreate(
            ['email_usu' => 'carlitosdelcarpio3@gmail.com'],
            [
                'cod_usuarios'  => 'USU001', // Asignamos el código manual VARCHAR(10)
                'nom_usu'       => 'Alvaro',
                'app_usu'       => 'Del Carpio',
                'apm_usu'       => null,
                'pas_usu'       => Hash::make('7879fullHD$'),
                'cod_roles_usu' => 1,
            ]
        );

        // =====================================================
        // 3. GERENTE
        // =====================================================
        Usuario::updateOrCreate(
            ['email_usu' => 'luissarmiento27@gmail.com'],
            [
                'cod_usuarios'  => 'USU002', 
                'nom_usu'       => 'Luis',
                'app_usu'       => 'Sarmiento',
                'apm_usu'       => null,
                'pas_usu'       => Hash::make('Gerente123#'),
                'cod_roles_usu' => 2,
            ]
        );

        // =====================================================
        // 4. TÉCNICO AUTOMOTRIZ
        // =====================================================
        Usuario::updateOrCreate(
            ['email_usu' => 'juanperezlopez1@gmail.com'],
            [
                'cod_usuarios'  => 'USU003',
                'nom_usu'       => 'Juan',
                'app_usu'       => 'Pérez',
                'apm_usu'       => 'López',
                'pas_usu'       => Hash::make('Técnico123#'),
                'cod_roles_usu' => 3,
            ]
        );

        // =====================================================
        // 5. CLIENTE (Usuario para Login)
        // =====================================================
        Usuario::updateOrCreate(
            ['email_usu' => 'carlosjarandilla12@gmail.com'],
            [
                'nom_usu'       => 'Carlos',
                'app_usu'       => 'Jarandilla',
                'apm_usu'       => 'Quisbert',
                'pas_usu'       => Hash::make('Cliente123#'),
                'cod_roles_usu' => 4,
            ]
        );

        // =====================================================
        // 6. TABLA CLIENTES (Llave primaria: cod_clientes [VARCHAR])
        // =====================================================
        Cliente::updateOrCreate(
            ['email_cli' => 'carlosjarandilla12@gmail.com'],
            [
                'cod_clientes' => 'CLI001', // Asignamos el código manual VARCHAR(20)
                'nom_cli'      => 'Carlos',
                'app_cli'      => 'Jarandilla',
                'apm_cli'      => 'Quisbert',
                'tel_cli'      => '77201419',
                'dir_cli'      => 'La Paz, Bolivia',
                'email_cli'    => 'carlosjarandilla12@gmail.com',
                'img_cli'      => null, // Añadido para cumplir con la estructura SQL
            ]
        );

        // Imprimir reporte en consola
        $this->command->info('✅ Roles, Usuarios y Clientes sincronizados correctamente.');
    }
}