<?php

namespace Statamic\Addons\Version;

use Statamic\Extend\Tasks;
use Illuminate\Console\Scheduling\Schedule;

class VersionTasks extends Tasks
{
    use Outpost;

    /**
     * Define the task schedule
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     */
    public function schedule(Schedule $schedule)
    {
        $schedule->call(function() {
            if ($this->isUpdateAvailable())
            {
                $this->sendNotifications();
            }
        })->daily();
    }
}
