Hooks
---

There are a number of hooks that extensions can make use of in order to enhance/change  the functionality of the core. Hooks are defined in the `includes/core/Site.php`


* `hook_permission` - add permissions to the site
* `hook_menu` - add new items to the menu
* `hook_model` - @deprecated
* `hook_url` - define custom url routes (routes urls to controllers)
* `hook_libraries` - include custom php libraries (APIs)
* `hook_cron` - execute actions durring cron
* `hook_twig_function` - define new twig functions to use in templates
* `hook_preprocess_page` - execute actions before processing the page
* `hook_preprocess_boot` - execute actions before booting
* `hook_postprocess_boot` - execute actions after booting and before any routing begins