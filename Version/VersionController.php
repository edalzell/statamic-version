<?php

namespace Statamic\Addons\Version;

use Statamic\Extend\Controller;

class VersionController extends Controller
{
    use Outpost;

    public function getSendNotifications()
    {
        $this->authorize('super');

        $this->sendNotifications();
    }
}
