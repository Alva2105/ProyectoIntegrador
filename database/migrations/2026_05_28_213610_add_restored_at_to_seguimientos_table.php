<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('seguimientos', 'restored_at')) {
            Schema::table('seguimientos', function (Blueprint $table) {
                $table->timestamp('restored_at')->nullable()->after('deleted_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('seguimientos', 'restored_at')) {
            Schema::table('seguimientos', function (Blueprint $table) {
                $table->dropColumn('restored_at');
            });
        }
    }
};