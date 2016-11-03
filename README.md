Atomar
---

Atomar is an oppinionated web-app development framework that follows the Model View Controller design pattern using existing open source technologies.

* **model**: [ReadBeanPHP] is an easy ORM for PHP and on-the-fly relational mapper
* **view**: [Twig] is a flexible, fast, and secure template engine for PHP
* **controller**: Atomar uses a currated set of custom controllers.

###Requirements
* PHP 7.0

##Docs
> We are slowly moving the documentation into the wiki. Please bear with us while transition from documentation every to just one place.

##Want to learn more?
The rest of the documentation is broken up by class. You can find the documentation on disk in `doc/` or, if you have already installed Atomar, in your browser to [/atomar/documentation](/atomar/documentation).

##Sample Site Layout
Below is a sample directory structure for a site built with Atomar.

    ── www
        ├── .atomar/
        ├── app/
        ├── ext/
        ├── .htaccess
        ├── config.json
        └── index.php
###ext/
This directory contains all the Atomar extensions. Extensions add extra functionality for your site or other extensions. 

* [Extensions](/atomar/documentation/core/Extensions)


##Atomar Folder Structure
Below is an overview of the Atomar directory structure followed by descriptions of the items.

    ──.atomar
        ├── assets/
        │   ├── css/
        │   ├── fonts/
        │   ├── img/
        │   └── js/
        ├── atomar/
        │   ├── controller
        │   ├── core
        │   ├── exception    
        │   └── hook
        ├── doc/
        ├── example/
        ├── model/
        ├── vendor/
        └── views/

###assets/
Contains the core assets. These files are loaded in the core by default. See also

* [JavaScript](/atomar/documentation/core/Javascript)

###controller/
Contains the core controllers.

* [Controllers](/atomar/documentation/core/Controllers)

###atomar/
Contains classes that make up the atomar core.

* [Authentication](/atomar/documentation/core/Authentication)
* [Configuration](/atomar/documentation/core/Configuration)
* [Cron](/atomar/documentation/core/Cron)
* [Functions](/atomar/documentation/core/Functions)
* [Hooks](/atomar/documentation/core/Hooks)
* [Lightboxes](/atomar/documentation/core/Lightboxes)
* [Migration](/atomar/documentation/core/Migration)
* [Users](/atomar/documentation/core/Users)
* [APIs](/atomar/documentation/core/APIs)
* [Menus](/atomar/documentation/core/Menus)

###vendor/
Contains third party code used in the core. Included in this directory is the source for [ReadBeanPHP], [Twig], and [Glue].

###model/
Contains all the core models. These models are slightly different from extension models because they extend CoreBeanModel instead of BeanModel in order to gain a few extra security features. A model here is just an extra wrapper around a bean so you can add extra methods or perform pre/post-processing. Read the complete documentation at [ReadBeanPHP].

###views/
Contains all the core views. A view is a [Twig] template and you can read their documentation for details.


[Glue]:http://gluephp.com
[ReadBeanPHP]:http://redbeanphp.com/
[Twig]:http://twig.sensiolabs.org/
