<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Notifications;

class DeleteNotifications extends Command
{
    protected $signature = 'notifications:delete';

    protected $description = 'Delete old notifications';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $today = Carbon::today();
        $oldDate = $today->subDays(10);
        Notifications::where('created_at', '<=', $oldDate)->delete();
        $this->info('Old notifications deleted successfully.');
    }
}
