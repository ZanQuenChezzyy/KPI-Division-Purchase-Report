<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('purchase_requisition_item_id')->constrained('purchase_requisition_items')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->unsignedSmallInteger('qty')->length(5);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('total_price')->nullable();
            $table->unsignedSmallInteger('received_qty')->length(5)->default(0);
            $table->unsignedTinyInteger('status')->default(0)->length(1);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
