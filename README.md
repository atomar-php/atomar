> UPDATE: I started this framework a few years ago because I couldn't find a good framework that I liked. However, since then [Laravel](https://laravel.com/) has matured a lot and does everything and a lot more that I was trying to accomplish. So I am at long last laying this to rest.

Atomar
---

Atomar is an opinionated web-app development framework that follows the Model View Controller design pattern using existing open source technologies.

* **model**: [ReadBeanPHP](http://redbeanphp.com/) is an easy ORM for PHP and on-the-fly relational mapper
* **view**: [Twig](http://twig.sensiolabs.org/) is a flexible, fast, and secure template engine for PHP
* **controller**: Atomar uses a currated set of custom controllers.

## Requirements
* PHP 7.0

## Docs
> We are slowly moving the documentation into the wiki. Please bear with us while we transition from documentation everywhere to just one place.
> For now just about everything in the wiki is outdated.

You can learn more about the system at https://github.com/neutrinog/atomar/wiki.

## CLI
You ask if we have a command line client? Why yes we do.

https://github.com/neutrinog/node-atomar-cli

## Performance

Considering all the goodness Atomar gives you... Good enough.

A controller that does basically nothing will load in in about 200ms.
That can seem crazy slow! However, consider these comparisons:

| Site      | Waiting to download | DOM loaded |
|-----------|---------------------|------------|
| Atomar    | 200ms               | 1.2s       |
| Facebook  | 55ms                | 1.5s       |
| Microsoft | 400ms               | 1.6s       |

> The moral of the story is that **response times don't matter**.
Only the time it takes for the DOM to load.

#### Disclaimer
I ran these test on Jan 17, 2017 in Chrome by opening the developer tools on each site and reloading the page several times.
The values above are not the result of rigorous testing.
