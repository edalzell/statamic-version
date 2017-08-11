<?php

namespace Statamic\Addons\Version;

use Statamic\Extend\Controller;

class VersionController extends Controller
{
    use Outpost;

    public function getSendNotifications()
    {
        $this->sendNotifications();
    }

    public function getUpdateAvailable()
    {
        return response(bool_str($this->isUpdateAvailable()));
    }
}
