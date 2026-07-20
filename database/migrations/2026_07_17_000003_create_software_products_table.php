<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('default_license_type')->nullable();
            $table->string('billing_frequency')->nullable();
            $table->string('url')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_products');
    }
};
