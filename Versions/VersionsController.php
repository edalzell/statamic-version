<?php

namespace Statamic\Addons\Versions;

use Statamic\Extend\Controller;

class VersionsController extends Controller
{
    use Outpost;

    public function outdatedAddons()
    {
        return $this->view('outdated-addons', ['addons' => $this->getOutdatedAddons(), 'title' => 'Addon Updates']);
    }

    public function getSendNotifications()
    {
        $this->authorize('super');

        $this->sendNotifications();
    }
}
