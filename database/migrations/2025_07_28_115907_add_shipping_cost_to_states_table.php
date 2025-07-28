<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::table('states', function (Blueprint $table) {
            $table->decimal('shipping_cost', 8, 2)->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('states', function (Blueprint $table) {
            $table->dropColumn('shipping_cost');
        });
    }
};
