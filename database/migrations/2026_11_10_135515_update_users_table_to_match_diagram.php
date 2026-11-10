<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aligne la table users sur le diagramme du projet.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('name')->change();
            $table->text('email')->change();
            $table->longText('password')->change();
            $table->text('remember_token')->nullable()->change();
            $table->string('unit')->default('metric');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('unit');
            $table->string('name')->change();
            $table->string('email')->change();
            $table->string('password')->change();
            $table->rememberToken()->change();
        });
    }
};
