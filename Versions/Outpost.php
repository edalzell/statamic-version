<?php

namespace Statamic\Addons\Versions;

use Github\Client;
use Statamic\API\Str;
use Statamic\API\Cache;
use Statamic\API\Email;
use Statamic\API\Config;
use Statamic\Extend\Addon;
use Statamic\Extend\Extensible;
use Statamic\Extend\Management\AddonRepository;

trait Outpost
{
    use Extensible;

    /**
     * Where the cached addons will be stored
     */
    const ADDONS_CACHE_KEY = 'outdated_addons';

    /** @var \Statamic\Outpost\Outpost  */
    private $outpost = null;

    /** @var \Illuminate\Support\Collection  */
    private $outdatedAddons = null;

    public function __construct()
    {
        $outpost = version_compare(STATAMIC_VERSION, '2.10.0', '<') ? 'Statamic\Outpost' : 'Statamic\Outpost\Outpost';
        $this->outpost = app($outpost);
        $this->radio();
    }

    private function radio()
    {
        $this->outpost->radio();

        if ($this->hasCachedAddons()) {
            $this->outdatedAddons = $this->getCachedAddons();
        } else {
            $this->loadOutdatedAddons();
            $this->cacheAddons();
        }
    }

    private function hasCachedAddons()
    {
        return Cache::has(self::ADDONS_CACHE_KEY);
    }

    private function getCachedAddons()
    {
        return Cache::get(self::ADDONS_CACHE_KEY);
    }

    public function areAddonUpdatesAvailable()
    {
        return $this->outOfDateAddons->count() > 0;
    }

    public function loadOutdatedAddons()
    {
        $this->outOfDateAddons = app(AddonRepository::class)
            ->thirdParty()
            ->addons()
            ->map(function ($addon, $ignore) {
                if ($this->isGithubUrl($addon->meta()->get('url'))) {
                    $latestVersion = $this->latestAddonVersion($addon);
                    if (version_compare($addon->version(), $latestVersion, '<')) {
                        return $addon->meta()->set('latest_version', $latestVersion);
                    }
                }

                return null;
            })->filter(function ($meta) {
                return $meta;
            });
    }

    private function cacheAddons()
    {
        Cache::put(self::ADDONS_CACHE_KEY, $this->outdatedAddons, 60);
    }

    private function isGithubUrl($url)
    {
        return Str::contains($url, 'github');
    }

    /**
     * Undocumented function
     *
     * @param Addon $addon
     * @return string
     */
    private function latestAddonVersion($addon)
    {
        list($ignore, $user, $repo) = explode(
            '/',
            parse_url($addon->url(), PHP_URL_PATH)
        );

        $client = new Client();

        return array_get(
            $client->api('repo')->releases()->latest($user, $repo),
            'name'
        );
    }

    /**
     * @return bool
     */
    public function areUpdatesAvailable()
    {
        return $this->outpost->isUpdateAvailable() ||
               $this->areAddonUpdatesAvailable();
    }

    /**
     * @return void
     */
    public function sendNotifications()
    {
        $email_config = collect($this->getConfig('notifications'))->first(function ($ignored, $value) {
            return($value['type'] == 'email');
        });

        collect($email_config['addresses'])->each(function ($email_address, $ignored) use ($email_config) {
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
