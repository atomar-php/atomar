Controllers
----
>Looking for Lightboxes? Go [here](/atomar/documentation/core/Lightboxes)

Controllers handle all of the page logic and are activated when their route is visited by a user. Every controller extends the `Controller` class and containe two primary methods

    function GET($matches) {

    }

    function POST($matches) {

    }

You may of course add any method you want including additional http methods such as `PUT` and `HEAD`. Just make sure any http methods are uppercase to ensure they are called correctly.

###Navigation
There are a few different ways to navigate within a controller.

*   `$this->go($url)` - Navigates to a new page. If you leave $url blank it will just refresh the page.
*   `$this->go_back()` - Navigates to the previous url. If there is no return url in the session this will just reload the current page.
*   `$this->throw404()` - Does exactly what you think it does.


###Rendering
Controllers are rendered by providing a template file and an optional array of options/variables.

    $this->render_view($template, $options);

###Templating
There are no rules regarding how you build your templates. There are a few different templates provided by the core. The main template is `views/_base.html` and you are encouraged to use it, you may create your own version of `_base.html` and store it in your extension e.g. `includes/extensions/my_extension/views/_base.html`.

    {% extends "_base.html" %}

    {% block title %}My page title{% endblock %}

    {% block content %}
    // put some html here
    {% endblock %}

Other templates available are

* `views/_base.html` - default includes the primary navigation with some basic [Boostrap](http://getbootstrap.com/) based structure.
* `views/_plain.html` - does not include the primary navigation 
* `views/_non_responsive.html` - non responsive template... sort of this hasn't actually been used or tested.

For details on how the templating system works please refer to [Twig].

###Exception Handling
Exceptions that occure within a controller of any of it's subclasses (e.g. [Lightboxes](/atomar/documentation/core/Lightboxes) and [ApiControllers](/atomar/documentation/core/ApiControllers)) can be handled by defining an `exception_handler` method in the class instance. This method will be called and passed the exception object.

[Twig]:http://twig.sensiolabs.org/