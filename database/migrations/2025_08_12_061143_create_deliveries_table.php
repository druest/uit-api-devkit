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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->date('delivery_date');
            $table->string('delivery_code')->unique();
            $table->string('customer_delivery_number')->nullable();
            $table->smallInteger('customer_id')->index();
            $table->foreignId('delivery_type_id')->constrained('delivery_types');
            $table->foreignId('status_id')->constrained('delivery_statuses');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
