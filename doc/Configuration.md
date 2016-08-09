Site Configuration
----

Atomar runs off a single configuration file located at `includes/core/config.php`. In a fresh install of atomar there is a template file `config.default.php` that must be copied to create the configuration file.

The site configuration can be accessed during runtime through the Site class `S` for example `S::$config['site_name']` will return the name of the website. The configuration array is instantiated from a custom read only array class to prevent changes to the configuration during run time.

##Debug
The debug configuration is added to the site configuration at run time when the system boots up. You can check to see if the site is in debug mode by evaluating the value of `S::$config['debug'];`. The site can be put in/out-of debug mode from the [admin page](/admin)

##Suggestions

The directory defined by `S::$config['files']` should for security purposes exist outside of the web root. The `S::$config['cache']` directory however should always exist within the web root in order to support extensions that peform caching. Both of these directories must be writeable by the `www-data` or equivalent user.

If you are uploading, downloading, or creating a file you are stongly encouraged to do so somewhere within the files directory as defined by `S::$config['files']`. Doing so will ensure the system remains fully extensible and will prevent a lot of clutter.