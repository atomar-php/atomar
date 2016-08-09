JavaScript Functions
---

There are a number of different functions and classes available in the core. Below is a list of these along with some basic instructions on how to use them. As always please refer to the well documented source code for further details. Javascript exists in `assets/`. Available functions are defined in `assets/main.js` while classes are defined in their own appropriately named files`.

>TODO: The javascript organization needs a little tlc.

>Also, JavaScript is usually camel case. Please use camel case when using Atomar even if you see old code indicating otherwise. Some code is old and will need to be refactored.

#Classes

Most of these classes will just work if you provide the nessesary data attributes because they are loaded when the document loads. However, if you add an element to the dom after it has loaded you will need to manually initialize it on that element. Please refer to `assets/main.js` for examples.

###InlineEdit
This class is loaded by default and allows you to easily make an element editable by adding a few data attributes to the html. Here is a trimmed example from the [Settings](/admin/settings) page. Given the setting id `12` and the database column `value` within the `settings` model/table:

    <span data-edit-id="12" data-edit-model="settings" data-edit-field="value">10</span>

An additional attribute may be added to specify the type of form to be displayed when the cursor hovers over the element `data-edit-type`. The default type is `input`. Other types include `textarea` and `date`.
>Note: the recent update to this class currently only supports input fields.

###Confirmation
This class allows you to quickly create a confirmation dialog on any element. This is useful if you are performing dangerous operations and want to verify the change. This class is very customizable and you can hook up callback functions to the `onOk` and `onCancel` hooks. However, in general you just need to add a data attribute to your link `data-confirm="Are you sure?"`. This will display an ok/cancel dialog to the user.

###Lightbox
The lightbox class is a powerful tool that allows developers to create functional and aesthetically pleasing lightboxes. Implimenting a lightbox is very easy. In place of the `href` include a `data-lightbox` attribute the value of which contains the appropriate url. This url will need to be routed to a Lightbox controller.

    <a data-lightbox="/admin/users/create">Create User</a>

See [Lightboxes](/admin/documentation/core/Lightboxes) for details about Lightbox controllers and [Extensions](/admin/documentation/core/Extensions) for details about setting up routes.

###Chosen
This is a third party library [Chosen JS](http://harvesthq.github.io/chosen/). Please see their documentation. A few enhancements have been made in order to integrate it nicely with Atomar, but these have been abstracted from the source so updates can be applied easily.

###Loader Animation
This class prepares a loading animation (spinning wheel) that can be used in a number of different ways. In general you can use the loader by calling it from the global variable `global`. Example: `global.loader.start()` `global.loader.stop()`. If you want to display the loader in a particular area of the page you can set the target `loader.setTarget($('.myclass'))`. At times you may need multiple loading animations on a single page. In that case you can spawn a new instance directly onto an element `var loader2 = loader.spawn($('.my-element'))`. And lastly you can specify whether or not the loader should be blocking (displays translucant overlay) `loader.blocking(true)`

For additional utilities please refer to the source code.

>TODO: This class is old and combersome.

###Validate
This class allows developers to easily add field validation to their forms. You can either validate a single field or tie multiple fields together for combined validation.

    data-validate="validation_name" data-quiet="" data-group="gropu_name_of_linked_fields" data-submit-btn="btn_to_disable_until_validated" data-strict="bool"

`data-strict` will prevent validation if the value of the input field hasn't changed. This is useful when editing existing data.

>TODO: This class needs to support passing validation handling to an extension.

###Register Global Variables
You can add global variables/functions by calling the below method.

  `RegisterGlobal('name', object);`

Once a variable has been registered you can access it anywhere using `global.name`.

###Register Startup Functions
Startup functions are executed when the document loads and take no arguments.
You can only register starup functions before the document loads. After this you will receive a notice in the console that startup functions cannot be registered during run time. You can register a startup function with the following:

    RegisterStartup(fun);

###Key Bindings
Binding to a key press in Javascript is a little combersome so Atomar provides some helpful triggers for popular key presses to make developing a little easier. Here are a list of currently supported keys

* Escape - `$('body').on('key.escape')`
* Return - `$('body').on('key.return')`

>TODO: key binding should be triggered on the window not the body.

#Functions

###delay(callback, delay)
Waits for a moment before executing the callback.


##Alerts

###close_alerts()
Closes all the active alert growl messages

###set_error(message)
Display an error growl message. Remains visible until the user dismisses it or until close_alerts() is called.

###set_warning(message)
Displays a warning growl message

###set_success(message)
Displays a success growl message

###set_notice(message)
Displays a notice growl message


##Date Time
>TODO: date functions should use the current date if no date is provided.

###strtodate(string)
Converts a string into a date object

###form_date(date)
Formats a date object into a form friendly (human readable) date string.

###db_date(date)
Formats a date object into a database friendly datetime string.

###fancy_date(date)
Formats a date object into a string of the form `May 16th 1986 at 6:45 pm`

###simple_date(date)
Formats a date object into a string of the form `May 16th 1986`

###fancy_time(seconds)
Formats seconds into a string of the form `hh:mm:ss`

###day_position(day)
Returns the day number append with it's position postfix. e.g. `1st, 2nd, 3rd, 4th`

>TODO: this method doesn't always work

###month(index)
Returns the three letter short name of a month given it's index starting at 0.

##String Utilities

###shade_color(color, percent)
Returns the shaded hexedecimal color. The input must be 7 character including the leading `#`. The valid values for percent are -1.0 to 1.0

###blend_colors(c0, c1, p)
Returns the blended hexedecimal color. The input must be 7 characters including the leading `#`. The valid values for p are 0.0 to 1.0

###rgb2hex(rgb)
Converts rgb color to hex color.

###nl2br(str, is_xhtml)
Replaces newlines with `<br />`

###br2nl(str, is_xhtml)
Replaces `<br />` with newlines.

###human2machine(human_string, separator)
Converts a human readable string to a machine readable string by replacing non-alphanumeric characters with the `separator`.

###padd(number, character, length)
Padds a string up to the specified `length` by prepending `character` to the front

###formatMoney(decPlaces, thouSeparator, decSeparator)
Number.prototype.formatMoney = function(decPlaces, thouSeparator, decSeparator)

###letterTrim(text, length)
Trims a string by letter to fit within the specified `length` including ellipsis.

##URLs

###parameterizeUrl(url, key, value)
Adds a key value parameter to a url while maintaining existing parameters.