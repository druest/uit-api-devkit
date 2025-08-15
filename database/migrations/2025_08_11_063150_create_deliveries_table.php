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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->string('email')->unique();
            $table->string('tax_id_number')->unique();
            $table->text('address');
            $table->date('payment_due_date')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->boolean('requires_final_tax')->default(false);
            $table->date('register_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();

            // Core info
            $table->string('name');
            $table->enum('type', ['unit', 'goods']); // Vendor specialization
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('google_maps_link')->nullable();

            // Bank info
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();

            // Documents
            $table->string('npwp_number')->nullable();      // Tax ID
            $table->string('vendor_photo')->nullable();     // Logo or storefront

            // Optional extras
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });


        Schema::create('drivers', function (Blueprint $table) {
            $table->id();

            // Personal info
            $table->string('name');
            $table->string('nik')->unique();              // National ID
            $table->date('birth_date');
            $table->text('address');                      // Full address
            $table->string('phone')->nullable();
            $table->string('photo')->nullable();          // Profile picture
            $table->enum('ownership', ['owned', 'rented'])->default('owned');
            $table->foreignId('vendor_id')->nullable()->constrained()->index();
            // Documents
            $table->string('ktp_photo')->nullable();      // Path to KTP image
            $table->string('sim_photo')->nullable();      // Path to SIM image
            $table->string('house_photo')->nullable();    // Path to house image

            // Location
            $table->string('google_maps_link')->nullable(); // Link to house location

            // Bank info
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();

            // Optional extras
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('emergency_contact')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->morphs('contactable'); // creates contactable_id + contactable_type
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('role')->nullable();
            $table->string('job_description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('origins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->string('code')->unique()->nullable();
            $table->string('name');
            $table->text('address');
            $table->decimal('latitude', 10, 7)->nullable()->index();
            $table->decimal('longitude', 10, 7)->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->foreignId('origin_id')->nullable()->constrained();
            $table->string('code')->unique()->nullable();
            $table->string('name');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_id')->nullable()->constrained();
            $table->string('name');
            $table->decimal('latitude', 10, 7)->nullable()->index();
            $table->decimal('longitude', 10, 7)->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('route_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->onDelete('cascade');
            $table->foreignId('expense_type_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2)->index();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('destination_prices', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('destination_id')->index();
            $table->decimal('price', 10, 2)->index();
            $table->date('effective_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();

            // Plate info
            $table->string('plate_full')->unique()->index();
            $table->string('plate_region');
            $table->string('plate_number');
            $table->string('plate_suffix');

            $table->enum('ownership', ['owned', 'rented'])->default('owned');
            $table->smallInteger('vendor_id')->nullable()->index();
            // Vehicle metadata
            $table->string('manufacturer');       // e.g. Hino, Honda
            $table->year('manufactured_year');    // e.g. 2020
            $table->string('type');               // e.g. Tronton, Ankle
            $table->unsignedTinyInteger('tire_count'); // e.g. 6, 10
            $table->string('bodywork');           // e.g. Fullbox, Wingbox
            $table->string('color');              // e.g. White, Blue
            $table->boolean('is_active')->default(true);

            // Dimensions
            $table->decimal('length', 6, 2)->nullable(); // in meters
            $table->decimal('width', 6, 2)->nullable();  // in meters
            $table->decimal('height', 6, 2)->nullable(); // in meters

            //administratives
            $table->date('tax_due_date')->nullable();         // Pajak kendaraan
            $table->date('stnk_expiry_date')->nullable();     // Masa berlaku STNK
            $table->string('bpkb_file')->nullable();          // Path to BPKB file (PDF or image)
            $table->string('stnk_file')->nullable();          // Path to BPKB file (PDF or image)
            $table->date('insurance_expiry_date')->nullable(); // Asuransi kendaraan
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('driver_unit_assignments', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('driver_id')->index();
            $table->smallInteger('unit_id')->index();
            $table->date('assignment_date');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('delivery_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. 'waiting_invoice'
            $table->string('label')->index();          // e.g. 'Waiting for Invoice'
            $table->string('color')->nullable(); // UI badge color
            $table->text('notes')->nullable();
            $table->boolean('is_terminal')->default(false); // e.g. completed/cancelled
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->date('delivery_date');
            $table->string('delivery_code')->unique();
            $table->string('customer_delivery_number')->nullable();
            $table->smallInteger('customer_id')->index();
            $table->smallInteger('origin_id')->index();
            $table->smallInteger('destination_id')->index();
            $table->smallInteger('route_id')->index();
            $table->decimal('price', 10, 2)->index();
            $table->foreignId('status_id')->constrained('delivery_statuses');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('work_order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. 'waiting_invoice'
            $table->string('label')->index();          // e.g. 'Waiting for Invoice'
            $table->string('color')->nullable(); // UI badge color
            $table->text('notes')->nullable();
            $table->boolean('is_terminal')->default(false); // e.g. completed/cancelled
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('work_order_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. 'waiting_invoice'
            $table->string('label')->index();          // e.g. 'Waiting for Invoice'
            $table->string('color')->nullable(); // UI badge color
            $table->text('notes')->nullable();
            $table->boolean('is_terminal')->default(false); // e.g. completed/cancelled
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('work_order_type_id')->index();
            $table->smallInteger('delivery_id')->nullable()->index();
            $table->smallInteger('unit_id')->index();
            $table->smallInteger('driver_id')->index();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->foreignId('work_order_status')->constrained()->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('work_order_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('route_expense_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2)->index();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('company_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->onDelete('cascade');
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('trf_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->onDelete('cascade');
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
        Schema::dropIfExists('units');
        Schema::dropIfExists('destination_prices');
        Schema::dropIfExists('route_expenses');
        Schema::dropIfExists('expense_types');
        Schema::dropIfExists('routes');
        Schema::dropIfExists('destinations');
        Schema::dropIfExists('origins');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('trf_expenses');
        Schema::dropIfExists('company_accounts');
        Schema::dropIfExists('work_order_expenses');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('work_order_types');
        Schema::dropIfExists('work_order_statuses');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('delivery_statuses');
        Schema::dropIfExists('driver_unit_assignments');
    }
};
