Template
---
When rendering templates you will no doubt wish to include custom css and JavaScript.

When injecting inline CSS or JS you are strongly encouraged to make use of the [HEREDOC](http://en.wikipedia.org/wiki/Here_document) syntax.

## CSS
There are two ways to include css on a page. You may either include an external file or inject css inline.

####External File

    S::$css[] = '/includes/extensions/my_extension/css/style.js'

####Inline CSS

    S::$cs_inline[] = <<<CSS
      a {
        color:#ddd;
      }
    CSS;

## JS

####External File

    S::$js[] = '/includes/extensions/my_extension/js/my_extension.js';

####Inline JS
JavaScript may also be injected inline to be loaded after the document loads.

    S::$js_onload[] = <<<JAVASCRIPT
      var myvar = 12;
    JAVASCRIPT

For details in how the actual templating system works please check out [Twig].

[Twig]:http://twig.sensiolabs.org/