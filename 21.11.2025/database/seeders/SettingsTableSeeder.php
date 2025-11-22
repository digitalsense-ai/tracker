<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('settings')->insert([
            ['key' => 'currency', 'value' => 'DKK'],
            ['key' => 'position_size', 'value' => '1000'],
            ['key' => 'fee_percent', 'value' => '0.1'],
            ['key' => 'take_profit_ratio', 'value' => '2'],
            ['key' => 'stop_loss_ratio', 'value' => '1'],
        ]);
    }
}
