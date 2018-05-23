# WP-CLI Plugin Bisect
A simple WP-CLI command to selectively deactivate plugins using a binary search to identify slow or broken plugins.

## What problem does this solve?
Have you ever been working on a WordPress site that is running super slow or isn't working correctly and you can't figure out which plugin is causing the problem?

WP-CLI Plugin Bisect is like `git bisect` for WordPress plugin activation. When you start it, it will soft-deactivate 50% of your plugins. You can then test to see if the problem is still occurring and tell Plugin Bisect whether the problem is fixed or still happening. It will keep winnowing your plugins down until the problem plugin is identified. 

### What do you mean by soft-deactivate?

When WP-CLI Plugin Bisect deactivates a plugin, it's only filtering the `active_plugins` setting in WordPress. So the plugin is temporarily deactivated, but disabling WP-CLI Plugin Bisect or ending the bisection returns everything to normal.

## Installation

1. Download or clone the repository into `wp-content/plugins/`
2. Activate the plugin in WP admin or using WP-CLI: `wp plugin activate wp-cli-plugin-bisect`

## Bisection

To search for the bad plugin, you first start bisection with this command:

`wp plugin-bisect start`

To end searching use:

`wp plugin-bisect end`

### Typical Search Process
1. Run `wp plugin-bisect start`
2. In your browser, test whether the problem is still occurring. 
3. If the problem is gone, run `wp plugin-bisect good`. 
4. If the problem is still happening, run `wp plugin-bisect bad`. 
5. Repeat until WP-CLI Plugin Bisect identifies the bad plugin with a message like this: **Success: Done! It looks like sfwd-lms/sfwd_lms.php is the culprit!**

## Caveats

- This plugin works best when there is a single plugin causing an issue. If more than one plugin is causing a problem, it will not be able to identify both. (See debugging below)
- There is no automatic handling of dependencies. So, if the problem plugin is a WooCommerce addon that doesn't load itself if WooCommerce is deactivated, it may not be able to identify the problem plugin.

## Debugging

You can add `--debug` to the end of any command for extra output. This may be useful when you are working with multiple problem plugins, or with a dependency because you can see the list of plugins being tested. 

For example, when running `wp plugin-bisect good --debug`, you will see something like this:
```txt
Debug (bootstrap): Running command: performance-bisect good (2.384s)
Debug: To test: Array
(
    [0] => sfwd-lms/sfwd_lms.php
    [1] => smart-offers/smart-offers.php
)
 (2.398s)
Debug: Untested: Array
(
    [0] => example-com-plugin-product-recommendations.php
)
```

**To test** are the plugins currently deactivated. *Any plugin not listed here is active if, you know, it was active before you started.*

**Untested** is a list of the plugins not being deactivated. This list might be empty at times during the process, which is totally normal. It acts as sort of a placeholder.

## What is a binary search?

Great question, you can read all about it here:
https://en.wikipedia.org/wiki/Binary_search_algorithm

