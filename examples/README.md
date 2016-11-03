Example implementation
---

This is an example implementation of the Atomar framework.

In order to get up and running you need 2 files in you web directory.

* **.htaccess** sets things up like fancy urls, routing, etc.
* **index.php** boots up the framework.

The other components may be placed wherever you like and include

* **.atomar/** the directory containing the Atomar framework.
* **ext/** the directory containing the Atomar extensions (the name of this directory is configurable)
* **app/** the directory containing the main application (the name of this directory is configurable)
* **config.php** the configuration file.

###Sample Directory Layout

    ── www
        ├── .atomar/
        ├── app/
        ├── ext/
        ├── .htaccess
        ├── config.json
        └── index.php