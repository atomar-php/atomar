Performance
---
When Atomar is ran in production mode Twig (the template engine) caches all the template files to improve speed. However, this also means you may have to wait awhile before seeing a change on the site. In addition to templates you may optionally cache CSS/JS files as well. Only CSS/JS files included in the hook `hook_preprocess_boot` will be cached. Inline CSS/JS are never cached.

Clearing the cache and refreshing whatever page you are expecting a change on should solve all your problems and perhaps even world hunger.

###Usage
Template caching occures automatically when the site is in production mode. You may enable CSS and JS caching by visiting [/admin/performance](/admin/performance).

Below is an example of adding some CSS and JS

    function my_extension_preprocess_boot() {
      S::$js[] = '/includes/extensions/my_extension/js/script.js';
      S::$css[] = '/includes/extensions/my_extension/css/style.css';
    }

###Pitfalls

####Relative Paths
Due to the nature of combining files and storing them in the cache it is important that any files you add to be cached do not rely on relative file paths to external resources. For example: images in CSS files.

####Windows + symbolic links
CSS caching relies on symbolic links in order to maintain resource dependencies with the core CSS (fonts, images, etc.). Symbolic links are not supported very well on windows so be sure your production environment is running on a unix box.