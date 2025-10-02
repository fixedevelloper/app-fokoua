<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['food','drink'])->default('food');
            $table->timestamps();
        });

        // 2. products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('image_url')->nullable();
            $table->decimal('price', 10, 2);
            $table->enum('type', ['food','drink']);
            $table->integer('preparing_time')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. tables
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->integer('number')->unique();
            $table->integer('capacity')->default(4);
            $table->enum('status', ['free','occupied','reserved'])->default('free');
            $table->timestamps();
        });

        // 4. orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('server_id')->nullable()->constrained('users','id')->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users','id')->nullOnDelete();
            $table->enum('status', ['pending','preparing','served','paid','cancelled'])->default('pending');
            $table->enum('status_payment', ['unpaid','paid','partial'])->default('unpaid');
            $table->decimal('total_food', 10, 2)->default(0);
            $table->decimal('total_drink', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->integer('preparing_time')->default(0);
            $table->timestamps();
        });

        // 5. order_items
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['food','drink']);
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('base_price', 10, 2);
            $table->enum('status', ['pending','preparing','completed','cancelled'])->default('pending');
            $table->timestamps();
        });

        // 6. accompaniments
        Schema::create('accompaniments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('max_free')->default(0);
            $table->decimal('price',10,2)->default(0); // 0 si inclus dans le menu
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 7. supplements
        Schema::create('supplements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
        Schema::create('accompaniment_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accompaniment_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_default')->default(false); // si proposé par défaut
            $table->timestamps();
        });

        // 8. order_item_accompaniment
        Schema::create('order_item_accompaniment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accompaniment_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        // 9. order_item_supplement
        Schema::create('order_item_supplement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplement_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });

        // 10. payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('method', ['cash','card','mobile']);
            $table->decimal('amount', 10, 2);
            $table->foreignId('cashier_id')->nullable()->constrained('users','id')->nullOnDelete();
            $table->timestamps();
        });

        // 11. cash_registers
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['cash','card','mobile']);
            $table->decimal('balance', 10, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('recipient_type',['server','cashier','admin']);
            $table->unsignedBigInteger('recipient_id');
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->enum('status',['sent','read','failed'])->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_item_supplement');
        Schema::dropIfExists('order_item_accompaniment');
        Schema::dropIfExists('supplements');
        Schema::dropIfExists('accompaniments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('tables');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
