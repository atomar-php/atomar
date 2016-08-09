Lightboxes
----
>Looking for Controllers? Go [here](/admin/documentation/core/Controllers)

Lightboxes are a special kind of [Controller](/admin/documentation/core/Controllers) that allow developers to easily display content in a modal window.

A lightbox is created by using the `data-lightbox` attribute in place of the `href` on an anchor element `<a/>`. See [Javascript](/admin/documentation/core/Javascript) for details.

To actually build the lightbox you must create a controller that extends the `Lightbox` controller class, and create a view that extends the `_lightbox.html` template. For an example we will look at the user creation lightbox.

* `controllers/CAdminUserCreate.php` - controller
* `views/admin/modal.user.create.html` - template
* `views/admin/users.html` - template in which the lightbox link is placed. e.g. `<a data-lightbox="....`

###Sandboxed
All lightboxes are sandboxed in an `iframe`. This means you can do whatever you want inside your lightbox without breaking the parent page. It is however possible to access the parent javascript environment by using the `parent` variable. Accessing a lightbox javascript environment from the parent is a little more difficult.

In general, interacting outside of your own javascript environment is discouraged. However, there are some cases in which this is nessesary.

###Navigation

Navigating in lightboxes is very similar to normal controllers. Most of the differences are handled for you in the Lightbox class.

*   `$this->go($url)` - Instead of navigating to a new page like in normal controllers this will navigate within the lightbox. You should only use this method to navigate to other lightboxes. This effectively allows you to create multi-paged lightboxes so users can experience a seamless lightbox interface. If you need to provide a link to a different lightbox in the template look at the **Linking one Lightbox to another** section below.
*   `$this->dismiss()` - closes the lightbox without reloading the parent page.
*   `$this->redirect($url=false)` - closes the lightbox and redirects the parent to a different page, or just reloads the current page is `$url` is left `false`. 

There are two other methods from the parent `Controller` class that haven't been tested in lightboxes but will eventually be supported.

*   `$this->go_back()` - Navigates to the previous url. If there is no return url in the session this will just reload the current lightbox.
*   `$this->throw404()` - Does exactly what you think it does.

When performing authorization checks in a lightbox it is standard to respond to a failed authorization by performing a plain redirect e.g. `$this->redirect()`. If a redirect loop may occur (e.g. the same authorization check is performed on the parent page as well) it is the responsibility of the parent page to perform the appropriate relocation. For example, in `\atomar
controllers\CUser.php` authorization failures are handled by the default handler which redirects to `/` or `500.html`.

###Configuration
Lightboxes are much more complicated than Controllers and require a little bit of configuration in order to render correctly. You may be able to skip this configuration part, but in most cases you will need to configure it a little bit.

Below are a list of configuration methods that may be used. These methods must be called before rendering the page otherwise they will have not affect.

*   `$this->auto_height($enabled=true)` - allows the lightbox to automatically adjust it's height after it has been displayed to the user.
*   `$this->height($height)` - sets the initial height of the lightbox window. This will be overridden if auto_height is used.
*   `$this->width($width)` - sets the initial width of the lightbox window
*   `$this->header($header)` - sets the text to use in the lightbox header/title. 
*   `$this->method($method)` - sets the method of submitting the form data. e.g. 'post' or 'get'.
*   `$this->action($action)` - sets the url to which the form data is submitted. By default this is the url to the current lightbox.
*   `$this->enctype(enctyp)` - sets the enctype of the form

###Rendering
Lightboxes are rendered in the same way as normal controllers.

    $this->render_view($template, $options);

###Templating
Every lightbox should inherit from the core lightbox tempalte in `views/_lightbox.html`. At the very minimum a lightbox template should look something like this.

    {% extends "_lightbox.html" %}

    {% block lightbox %}
      // Form code goes here
    {% endblock %}

The lightbox block is wrapped in a form element along with other logic nessesary for a lightbox to function. Therefore you merely need to add form elements. A cancel and submit button is also provided by default. In addition to defining the lightbox content you may also customize the lightbox controls and close button.

**Default close block**

    {% block lightbox_close %}
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    {% endblock %}

**Default controls block**

    {% block lightbox_controls %}
      <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Cancel</button>
      <button type="submit" class="btn btn-primary modal-submit" data-loading-text="Loading..." >Submit</button>
    {% endblock %}

###Template variables
Lightboxes come with a few template variables that make certain tasks a little easier. For both of these make sure to use the twig function `url_encode` when using these in links.

####lightbox.return_url
Use this url if you need to perform an action and return back to this lightbox. For example in the template `/!/my_extension/process/?r={{ lightbox.return_url|url_encode }}` will ensure that after performing the process at /!/my_extension/process/ control will be returned to the lightbox.

####lightbox.dismiss_url
Use this url if you need to perform an action and close the lightbox after finishing. For example in the template `/!/my_extension/process/?r={{ lightbox.dismiss_url|url_encode }}` will ensure that after performing the process at /!/my_extension/process/ the lightbox will be closed.

###Linking one Lightbox to another
If you need to provide a link to a new lightbox (as apposed to linking within in the code) you must add the `lightbox_id` as a url parameter to the link. For example `/my/secondary/lightbox?_lightbox_id={{ lightbox.lightbox_id }}`. Including the lightbox id as a parameter is important because it enables the new lightbox to communicate with the page (closing, callbacks, etc.).

###Exception Handling
See **Exception Handling** in [Controllers](/admin/documentation/core/Controllers)