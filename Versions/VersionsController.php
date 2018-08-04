<?php

namespace Statamic\Addons\Versions;

use Statamic\Extend\Controller;

class VersionsController extends Controller
{
    use Outpost;

    public function outOfDate()
    {
        return $this->view('out-of-date', ['addons' => $this->outOfDateAddons()]);
    }

    public function getSendNotifications()
    {
        $this->authorize('super');

        $this->sendNotifications();
    }
}
