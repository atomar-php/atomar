Atomar REST APIs
---
> Looking for extension API's? go [here](/admin/documentation/core/Extensions)

The Atomar core relies heavly on the use of API's.  An api is call by querying a url of the form `example.com/!/[process_name]` with url parameters as nessesary. Urls in this form are mapped to the API controller in `controllers/CAPI.php`.

###How it works
When an api call is made the API handler will run the appropriate code (in a switch statement) and then return to the previous page using the `go_back()` method. However some API methods may instead return a JSON and exit the script. Such an example can be found in the validation api calls.

If an API method cannot be found the site will respond with a 404 page.

###Methods
Most of the core APIs are specific to core functionality so we will only describe a view here.

####GET
* `/!/debug_mode?enable=1` - enables the debug mode on the site
* `/!/cron` - runs cron on the site