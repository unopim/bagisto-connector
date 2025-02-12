<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations
     */
    public function up()
    {
        DB::table('products')->orderBy('id')->chunk(5000, function ($products) {
            foreach ($products as $row) {
                $valuesJson = json_decode($row->values, true);
                if (! isset($valuesJson['common']['status'])) {
                    var_dump(
                        'skip due to already migrated', $row->id);
                    continue;
                }

                $statusValue = $valuesJson['common']['status'];

                $status = is_string($statusValue) && strtolower($statusValue) === 'true' ? 1 : 0;

                $valuesJson['common']['product_status'] = $statusValue;

                unset($valuesJson['common']['status']);

                DB::table('products')
                    ->where('id', $row->id)
                    ->update(['status' => $status, 'values' => json_encode($valuesJson)]);

                    var_dump('migrated $row->id', $row->id);
            }
        });

        DB::table('attributes')->where('code', 'status')->update(['code' => 'product_status']);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::table('products')->orderBy('id')->chunk(100, function ($products) {
            foreach ($products as $row) {
                $valuesJson = json_decode($row->values, true);

                if (! isset($valuesJson['common'])) {
                    $valuesJson['common'] = [];
                }

                $valuesJson['common']['status'] = $row->status === 1 ? 'true' : 'false';

                if (isset($valuesJson['common']['product_status'])) {
                    unset($valuesJson['common']['product_status']);
                }

                DB::table('products')
                    ->where('id', $row->id)
                    ->update(['values' => json_encode($valuesJson)]);
            }
        });

        DB::table('attributes')->where('code', 'product_status')->update(['code' => 'status']);
    }
};
