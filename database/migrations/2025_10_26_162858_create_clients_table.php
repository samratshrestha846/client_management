<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('email');
            $table->string('phone_number');
            $table->unsignedBigInteger('duplicate_group_id')->nullable()->index();
            $table->string('import_batch_id')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['company_name', 'email', 'phone_number'], 'clients_composite_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
