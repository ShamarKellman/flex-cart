<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrade migration for existing installations.
 * New installations already get the correct schema from the original migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add applied_coupons to carts if not present
        if (! Schema::hasColumn('carts', 'applied_coupons')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->json('applied_coupons')->nullable()->after('metadata');
            });
        }

        // Change minimum_amount from decimal to unsignedBigInteger on coupons
        Schema::table('coupons', function (Blueprint $table) {
            $table->unsignedBigInteger('minimum_amount')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('applied_coupons');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->decimal('minimum_amount', 10, 2)->nullable()->change();
        });
    }
};
