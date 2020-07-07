<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventstoreTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('eventstore', function (Blueprint $table) {
            $table->id();
            $table->uuid('stream_id')->index();
            $table->integer('version');
            $table->text('payload');
            $table->timestamp('timestamp');
            $table->string('type');

            $table->unique(['stream_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eventstore');
    }
}
