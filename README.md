# Versions
Get notified by email when there's a new version of Statamic or any of your addons

# NOTE
The addons can **only** be checked if they have Github URLs and they are public repos.

# Install
1. Copy files to `addons` folder
2. Run `php please update:addons`
3. Visit `http://yousite.com/cp/addons/version/settings` to set up the notifications
4. Ensure you've set up your cron job so that Tasks are [run](https://docs.statamic.com/addons/classes/tasks#starting)

# Usage
In the template you have 
```
{{ updates }}
  {{ type }}, {{ name }}, {{ url }}, {{ latest_version }}, {{ current_version }}
{{ /updates }}
```

* `type` is one of `addon` or `statamic`
* `url` is the link to the addon repo (or the Statamic changelog)

# License

[MIT License](http://emd.mit-license.org/)