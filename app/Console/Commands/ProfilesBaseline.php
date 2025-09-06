<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class ProfilesBaseline extends Command{protected $signature='profiles:baseline';public function handle(){ $this->info('Baseline run'); }}