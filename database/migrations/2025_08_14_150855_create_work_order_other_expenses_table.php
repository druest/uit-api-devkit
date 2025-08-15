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
        Schema::create('work_order_other_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders');
            $table->foreignId('expense_type_id')->constrained('expense_types');
            $table->decimal('amount', 10, 2)->index();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_billed_to_customer')->default(false);
            $table->enum('status', ['pending', 'completed', 'canceled'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('trf_expense_others', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_other_expense_id')->constrained('work_order_other_expenses');
            $table->foreignId('company_account_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2)->index();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_other_expenses');
        Schema::dropIfExists('trf_expense_others');
    }
};
