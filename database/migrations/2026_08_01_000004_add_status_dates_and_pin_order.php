<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dateTime('started_at')->nullable()->after('deadline_at');
            $table->dateTime('completed_at')->nullable()->after('started_at');
        });

        DB::table('todos')
            ->where('status', 'sedang_dikerjakan')
            ->update(['started_at' => DB::raw('updated_at')]);
        DB::table('todos')
            ->where('status', 'selesai')
            ->update(['completed_at' => DB::raw('updated_at')]);

        Schema::table('sticky_notes', function (Blueprint $table) {
            $table->timestamp('pinned_at')->nullable()->after('color');
            $table->unsignedInteger('pin_order')->nullable()->after('pinned_at');
            $table->index(['workspace_id', 'pinned_at', 'pin_order'], 'sticky_notes_workspace_pin_index');
        });
    }

    public function down(): void
    {
        Schema::table('sticky_notes', function (Blueprint $table) {
            $table->dropIndex('sticky_notes_workspace_pin_index');
            $table->dropColumn(['pinned_at', 'pin_order']);
        });

        Schema::table('todos', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'completed_at']);
        });
    }
};
