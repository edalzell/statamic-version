<?php

namespace Statamic\Addons\Versions;

use Statamic\Extend\Tasks;
use Illuminate\Console\Scheduling\Schedule;

class VersionsTasks extends Tasks
{
    use Outpost;

    /**
     * Define the task schedule
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     */
    public function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            if ($this->areUpdatesAvailable()) {
                $this->sendNotifications();
            }
        })->daily();
    }
}
