<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Symfony\Component\Console\Output\BufferedOutput;

class ProfilesRunController extends Controller
{
    public function run(Request $r)
    {
        $days=(int)($r->input('days',10));
        $id=$r->input('id');
        $output=new BufferedOutput;
        \Artisan::call('profiles:backtest-v2', ['--days'=>$days,'--profile'=>$id,'--vv'=>true], $output);
        return response()->json(['ok'=>true,'log'=>$output->fetch()]);
    }
}
