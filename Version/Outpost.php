<?php
/**
 * Created by PhpStorm.
 * User: erin
 * Date: 2017-08-10
 * Time: 5:14 PM
 */

namespace Statamic\Addons\Version;

use Statamic\API\Email;
use Statamic\API\Config;
use Statamic\Extend\Extensible;

trait Outpost
{
    use Extensible;

    /** @var \Statamic\Outpost  */
    private $outpost = null;

    public function __construct()
    {
        $outpost = version_compare(STATAMIC_VERSION, '2.10.0', '<') ? 'Statamic\Outpost' : 'Statamic\Outpost\Outpost';
        $this->outpost = app($outpost);
        $this->outpost->radio();
    }

    /**
     * @return bool
     */
    public function isUpdateAvailable()
    {
        return $this->outpost->isUpdateAvailable();
    }

    /**
     * @return void
     */
    public function sendNotifications()
    {
        $email_config = collect($this->getConfig('notifications'))->first(function ($ignored, $value) {
            return($value['type'] == 'email');
        });

        collect($email_config['addresses'])->each(function($email_address, $ignored) use ($email_config) {
            $this->sendEmail($email_address, $email_config);
        });
    }

    /**
     * @param $address string
     * @param $config array
     */
    private function sendEmail($address, $config)
    {
        Email::create()
            ->to($address)
            ->from($config['from'])
            ->subject($config['subject'])
            ->with([
                'changelog_url' => 'https://statamic.com/changelog',
                'latest_version' => $this->outpost->getLatestVersion(),
                'current_version' => STATAMIC_VERSION])
            ->template($config['template'])
            ->in('site/themes/' . Config::getThemeName() . '/templates')
            ->send();
    }
}
