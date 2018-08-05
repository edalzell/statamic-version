<?php

namespace Statamic\Addons\Versions;

use Statamic\API\Nav;
use Statamic\API\User;
use Statamic\Extend\Listener;

class VersionsListener extends Listener
{
    use Outpost;

    /**
     * The events to be listened for, and the methods to call.
     *
     * @var array
     */
    public $events = [
        'cp.nav.created' => 'nav',
    ];

    /**
     * Add PHP Info to the side nav
     * @param  Nav    $nav [description]
     * @return void
     */
    public function nav($nav)
    {
        /** @var \Statamic\Data\Users\User $user */
        $user = User::getCurrent();
        $addonUpdateCount = $this->getOutdatedAddons()->count();

        if ($user && $user->isSuper() && $addonUpdateCount > 0) {
            // first remove the Updater
            $statamicUpdate = $nav->get('tools.updater');

            $nav->remove('tools.updater');

            $updates = Nav::item('Updates')
                ->route('outdated-addons')
                ->icon('progress-two')
                ->badge($addonUpdateCount + $statamicUpdate->badge());

            $addonUpdates = Nav::item('Addons')
                ->route('outdated-addons')
                ->badge($addonUpdateCount);

            $updates
                ->add($statamicUpdate->name('Statamic'))
                ->add($addonUpdates);

            $nav->addTo(
                'tools',
                $updates
            );
        }
    }
}
