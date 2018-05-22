<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TblproductdataUpdate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblproductdata', function (Blueprint $table) {
            $table->integer('intStock')->default(0)->after('strProductCode');
            $table->decimal('decCost',10,2)->nullable()->after('intStock');
            $table->boolean('blnDiscontinued')->default(0)->after('decCost');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblproductdata', function (Blueprint $table) {
            $table->dropColumn('intStock');
            $table->dropColumn('decCost');
            $table->dropColumn('blnDiscontinued');
        });

    }
}
