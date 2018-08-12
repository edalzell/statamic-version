<?php

namespace Statamic\Addons\Versions;

use Github\Client;
use Statamic\API\Str;
use Statamic\API\Cache;
use Statamic\API\Email;
use Statamic\API\Config;
use Statamic\Extend\Addon;
use Statamic\Extend\Extensible;
use Github\Exception\RuntimeException;
use Statamic\Extend\Management\AddonRepository;

trait Outpost
{
    use Extensible;

    /**
     * Where the cached addons will be stored
     */
    private static $addons_cache_key = 'outdated_addons';

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
        return Cache::has(self::$addons_cache_key);
    }

    private function cacheAddons()
    {
        Cache::put(self::$addons_cache_key, $this->outdatedAddons, 60);
    }

    private function getCachedAddons()
    {
        return Cache::get(self::$addons_cache_key);
    }

    public function areAddonUpdatesAvailable()
    {
        return $this->outdatedAddons->count() > 0;
    }

    public function isStatamicUpdateAvailable()
    {
        return $this->outpost->isUpdateAvailable();
    }

    public function loadOutdatedAddons()
    {
        $this->outdatedAddons = app(AddonRepository::class)
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

    /**
     * Undocumented function
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOutdatedAddons()
    {
        return $this->outdatedAddons;
    }

    private function isGithubUrl($url)
    {
        // should be at least 3 segments, an empty one, then the username, then the repo.
        $segments = explode('/', parse_url($url, PHP_URL_PATH));

        return Str::contains($url, 'github') && count($segments) >= 3 && !empty($segments[1]) && !empty($segments[2]);
    }

    /**
     * Gets the latest release of the addon
     *
     * @param Addon $addon
     * @return string
     */
    private function latestAddonVersion($addon)
    {
        try {
            $client = new Client();

            list($ignore, $user, $repo) = explode('/', parse_url($addon->url(), PHP_URL_PATH));

            return array_get($client->api('repo')->releases()->latest($user, $repo), 'name');
        } catch (RuntimeException $re) {
            return '999';
        }
    }

    /**
     * @return bool
     */
    public function areUpdatesAvailable()
    {
        return $this->isStatamicUpdateAvailable() || $this->areAddonUpdatesAvailable();
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    private function updateData()
    {
        $updates = $this->outdatedAddons->map(function ($addon, $key) {
            return [
                'type' => 'addon',
                'name' => $addon->get('name'),
                'url' => $addon->get('url'),
                'latest_version' => $addon->get('latest_version'),
                'current_version' => $addon->get('version'),
            ];
        })->values()->all();

        if ($this->isStatamicUpdateAvailable()) {
            $updates[] = [
                'type' => 'statamic',
                'name' => 'Statamic',
                'url' => 'https://statamic.com/changelog',
                'latest_version' => $this->outpost->getLatestVersion(),
                'current_version' => STATAMIC_VERSION,
            ];
        }

        return $updates;
    }

    /**
     * @return void
     */
    public function sendNotifications()
    {
        $updates = $this->updateData();

        $email_config = collect($this->getConfig('notifications'))->first(function ($ignored, $value) {
            return($value['type'] == 'email');
        });

        collect($email_config['addresses'])->each(function ($email_address, $ignored) use ($email_config, $updates) {
            $this->sendEmail($email_address, $email_config, $updates);
        });
    }

    /**
     * @param $address string
     * @param $config array
     * @param $update array
     */
    private function sendEmail($address, $config, $updates)
    {
        Email::create()
            ->to($address)
            ->from($config['from'])
            ->subject($config['subject'])
            ->with(['updates' => $updates])
            ->template($config['template'])
            ->in('site/themes/' . Config::getThemeName() . '/templates')
            ->send();
    }
}
