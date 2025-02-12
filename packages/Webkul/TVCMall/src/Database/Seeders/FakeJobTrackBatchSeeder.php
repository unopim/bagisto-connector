<?php

namespace Webkul\TVCMall\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FakeJobTrackBatchSeeder extends Seeder
{
    public function run()
    {
        $limit = 10000;
        $arr[] = ['itemNo' => 0];

        for ($i = 0; $i <= $limit; $i++) {
            DB::table('job_track_batches')->insert([
                'state' => 'validating',
                'data' => json_encode($arr),
                'job_track_id' => 398
            ]);
        }
    }
}
