Atomar
---

Atomar is a modular Rapid Application Development framework built for developers that can be easily extended with reusable code.
Minimum PHP version 5.3.4

>**Atomar is not a turnkey solution**, but attempts to provide easy-to-use tools while solving some of the basic everyday issues of web development.


## Design Pattern
Atomar implements the Model View Controller ([MVC](http://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller)) design pattern.



###Model
[ReadBeanPHP] is an easy ORM for PHP and on-the-fly relational mapper

###View
[Twig] is a flexible, fast, and secure template engine for PHP

###Glue
[Glue] is a simple PHP class that maps URLs to classes

##Want to learn more?
The rest of the documentation is broken up by class. You can find the documentation on disk in `doc/` or, if you have already installed Atomar, in your browser to [/admin/documentation](/admin/documentation).

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

* [Extensions](/admin/documentation/core/Extensions)


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

* [JavaScript](/admin/documentation/core/Javascript)

###controller/
Contains the core controllers.

* [Controllers](/admin/documentation/core/Controllers)

###atomar/
Contains classes that make up the atomar core.

* [Authentication](/admin/documentation/core/Authentication)
* [Configuration](/admin/documentation/core/Configuration)
* [Cron](/admin/documentation/core/Cron)
* [Functions](/admin/documentation/core/Functions)
* [Hooks](/admin/documentation/core/Hooks)
* [Lightboxes](/admin/documentation/core/Lightboxes)
* [Migration](/admin/documentation/core/Migration)
* [Users](/admin/documentation/core/Users)
* [APIs](/admin/documentation/core/APIs)
* [Menus](/admin/documentation/core/Menus)

###vendor/
Contains third party code used in the core. Included in this directory is the source for [ReadBeanPHP], [Twig], and [Glue].

###model/
Contains all the core models. These models are slightly different from extension models because they extend CoreBeanModel instead of BeanModel in order to gain a few extra security features. A model here is just an extra wrapper around a bean so you can add extra methods or perform pre/post-processing. Read the complete documentation at [ReadBeanPHP].

###views/
Contains all the core views. A view is a [Twig] template and you can read their documentation for details.


[Glue]:http://gluephp.com
[ReadBeanPHP]:http://redbeanphp.com/
[Twig]:http://twig.sensiolabs.org/