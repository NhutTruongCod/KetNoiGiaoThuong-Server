<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixNotificationUrls extends Command
{
    protected $signature = 'notifications:fix-urls';
    protected $description = 'Fix notification action_url to match API routes';

    public function handle()
    {
        // Fix /promotions/ -> /promotion/
        $count = DB::table('notifications')
            ->where('action_url', 'like', '%/promotions/%')
            ->update([
                'action_url' => DB::raw("REPLACE(action_url, '/promotions/', '/promotion/')")
            ]);
        
        $this->info("Fixed {$count} promotion URLs");
        
        return 0;
    }
}
