<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->unique(['workspace_id', 'slug']);
            $table->index(['is_system', 'slug']);
        });

        DB::table('categories')->insert([
            ['name' => 'Meeting', 'slug' => 'meeting', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Report Progress', 'slug' => 'report-progress', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Lainnya', 'slug' => 'lainnya', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('todos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 30)->default('belum_dikerjakan');
            $table->dateTime('deadline_at');
            $table->timestamps();
            $table->index(['workspace_id', 'status', 'deadline_at']);
            $table->index(['workspace_id', 'category_id']);
        });

        Schema::create('todo_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);
            $table->dateTime('scheduled_at');
            $table->string('status', 20)->default('scheduled');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'scheduled_at']);
            $table->index(['todo_id', 'kind']);
        });

        Schema::create('reminder_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_id')->constrained('todo_reminders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['reminder_id', 'user_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_deliveries');
        Schema::dropIfExists('todo_reminders');
        Schema::dropIfExists('todos');
        Schema::dropIfExists('categories');
    }
};
