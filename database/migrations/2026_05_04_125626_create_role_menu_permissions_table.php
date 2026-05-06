<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('route');
            $table->string('icon');
            $table->integer('order')->default(0);
            $table->boolean('is_admin_only')->default(false);
            $table->timestamps();
        });

        Schema::create('role_menu_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('menu_id');
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('menu_id')->references('id')->on('menu_items')->cascadeOnDelete();
            $table->unique(['role_id', 'menu_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_menu_permissions');
        Schema::dropIfExists('menu_items');
    }
};
