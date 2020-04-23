<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConexionesDomicilioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::connection("sqlite")->create('ConexionesDomicilio', function (Blueprint $table) {
            $table->id();
            $table->integer("IDRestaurante")->unique();
            $table->string("IP");
            $table->string("Nombre_Servidor");
            $table->text("Instancia");
            $table->unsignedInteger("Puerto");
            $table->text("BDD");
            $table->text("Usuario");
            $table->text("Clave");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection("sqlite")->dropIfExists('ConexionesDomicilio');
    }
}
