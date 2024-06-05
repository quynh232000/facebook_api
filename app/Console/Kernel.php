<?php

namespace App\Console;

use App\Models\Post;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->call(function () {
        //    $posts = Post::where('status','pedding')->get();
        //    foreach ($posts as $key => $post) {
        //     $collection = Http::withHeaders([
        //         'content-type'=>'text/plain',
        //         'apiKey'=>env('BAD_WORDS_API')
        //     ])->post('https://api.apilayer/bad_words',[
        //         $post->content
        //     ]);

        //     if($collection['bad_words_total']>0){
        //         // bad word
        //     }
        //    }
        // });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
