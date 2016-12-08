<?php

namespace atomar\core;

/**
 * The lightbox extends the controller to provide some
 * advanced functionality to the bootstrap modal.
 */
abstract class Lightbox extends Controller {
    /**
     * @var int This is the unique identifer for this lightbox.
     */
    private $id = false;
    /**
     * @var string The initial width of the lightbox.
     */
    private $width = 500;
    /**
     * @var string The initial height of the lightbox.
     */
    private $height = 300;
    /**
     * @var bool Flag to determine if the lightbox should atomatically
     * resize to the content height.
     */
    private $auto_height = true;
    /**
     * @var bool Flag to determine if the lightbox should atomatically
     * resize to the content width.
     * @deprecated since v2.0.10 it is not trivial to automatically determine the width
     */
    private $auto_width = false;
    /**
     * @var string The header of the lightbox.
     */
    private $header = '';

    /**
     * @var string the enctype of the lightbox form.
     */
    private $enctype = 'application/x-www-form-urlencoded';
    /**
     * @var string The method in which the form data is submitted.
     */
    private $method = 'post';
    /**
     * @var string The url to which the form data is submitted.
     */
    private $action = '';
    /**
     * @var string the current request url.
     */
    private $request = '';

    function __construct() {
        parent::__construct();
        $request = explode('?', $_SERVER["REQUEST_URI"]);
        $this->request = $request[0];
        $this->action = $request[0];
        $this->id = $_REQUEST['_lightbox_id'];

        if (!$this->id) {
            Logger::log_error('The lightbox id is missing. This may cause serious navigation problems. It is likely your server is not correctly passing get parameters.', $this->request);
        }

        // execute the return callback
        if (isset($_REQUEST['_dismiss']) || isset($_REQUEST['_returned'])) {
            $this->RETURNED();
        }

        // allow lightboxes to be dismissed before they load
        if (isset($_REQUEST['_dismiss'])) {
            $this->redirect();
        }

        Templator::$css_inline[] = <<<CSS
/*html {*/
  /*overflow:hidden!important;*/
/*}*/
CSS;

    }

    /**
     * This method will be called before GET, POST, and PUT when the lightbox is returned to e.g. when using lightbox.dismiss_url or lightbox.return_url
     */
    abstract function RETURNED();

    /**
     * Triggers the parent window to redirect to a different page.
     * Leaving the argument blank will cause the parent to refresh
     * which effectively closes the lightbox and allows any changes to be
     * re-loaded.
     * @param string $url The url to which the parent page will be redirected
     */
    protected function redirect($url = '') {
        $id = $this->id;
        Templator::$js_onload = array(); // clear any leftovers
        Templator::$js_onload[] = <<<JAVASCRIPT
parent.$(parent.window.document).trigger('lightbox.redirect', {
  id:'$id',
  url:'$url'
});
JAVASCRIPT;
        echo parent::renderView('@atomar/views/_lightbox_utility.html', array(), array(
            'render_messages' => false,
            'render_menus' => false
        ));
        exit;
    }

    /**
     * This method will be called automatically to handle any exceptions in the class
     *
     * @param Exception $e the exception
     */
    public function exceptionHandler($e) {
        Logger::log_error($e->getMessage(), $e->getTrace());
        set_error('A Lightbox exception has occured. See the log for details.');
        $this->dismiss();
    }

    /**
     * Triggers the parent windows to close the lightbox without
     * refreshing or redirecting to a different page.
     * @param string $js optional javascript that will be executed by the parent after the lightbox is dismissed.
     */
    protected function dismiss($js = '') {
        $id = $this->id;
        $alerts = array();
        // display any pending alerts
        foreach ($_SESSION['messages'] as $type => $messages) {
            $alerts[$type] = $messages;
            $_SESSION['messages'][$type] = array();
        }
        if (count($alerts)) {
            $alerts = json_encode($alerts);
        } else {
            $alerts = false;
        }
        Templator::$js_onload = array(); // clear any leftovers
        Templator::$js_onload[] = <<<JAVASCRIPT
parent.close_alerts();
var alerts = '$alerts';
if (alerts != false) {
  alerts = JSON.parse(alerts);
  for(var type in alerts) {
    parent.$.each(alerts[type], function(index, a) {
      parent.window['set_'+type](a);
    });
  }
}
parent.$(parent.window.document).trigger('lightbox.dismiss',  {
  id:'$id',
  callback:function() {

    $js
  }
});
JAVASCRIPT;
        echo parent::renderView('@atomar/views/_lightbox_utility.html', array(), array(
            'render_messages' => false,
            'render_menus' => false
        ));
        exit;
    }

    /**
     * @return int the lightbox id
     */
    protected function id() {
        return $this->id;
    }

    /**
     * When enabled the lightbox will resize to fit the content height
     * during the initialization.
     * @param bool $enabled set to true to enable automatic height.
     */
    protected function auto_height($enabled = true) {
        $this->auto_height = $enabled === true;
    }

    /**
     * When enabled the lightbox will resize to fit the content width
     * during the initialization.
     * @param bool $enabled set to true to enable automatic height.
     */
    protected function auto_width($enabled = true) {
        $this->auto_width = $enabled === true;
    }

    /**
     * Sets the initial height of the lightbox.
     * @param int $height the initial height of the lightbox
     */
    protected function height($height) {
        $this->auto_height = false;
        $this->height = $height;
    }

    /**
     * Sets the initial width of the lightbox.
     * @param int $width the initial width of the lightbox
     */
    protected function width($width) {
        $this->width = $width;
    }

    /**
     * Sets the header of the lightbox.
     * @param string $header the text to display in the lightbox header
     */
    protected function header($header) {
        $this->header = $header;
    }

    /**
     * Sets the enctype of the lightbox form.
     * @param string $enctype the default is application/x-www-form-urlencoded. You may also specify text/plain or multipart/form-data
     */
    protected function enctype($enctype) {
        $this->enctype = $enctype;
    }

    /**
     * Sets the method to use for submitting the data
     * @param string $method allowed values are 'post' or 'get'
     */
    protected function method($method) {
        $this->method = strtolower($method) == 'post' ? 'post' : 'get';
    }

    /**
     * Sets form action. The url the data is submitted to.
     * @param string $action the url to which the form data will be submitted.
     */
    protected function action($action) {
        $this->action = $action;
    }

    /**
     * Renders the view and does some extra processing
     * @param string $view the relative path to the view that will be rendered
     * @param array $args custom options that will be sent to the view
     * @param array $options optional rules regarding how the template will be rendered.
     * @return string the rendered html
     */
    protected function renderView($view, $args = array(), $options = array()) {
        $id = $this->id;
        $auto_height = $this->auto_height;
        $auto_width = $this->auto_width;
        $height = $this->height;
        $width = $this->width;

        // CSS
        Templator::$css_inline[] = <<<CSS
body {
  padding: 0;
}
CSS;

        // JS
        Templator::$js_onload[] = <<<JAVASCRIPT
var lbox = new Box();
RegisterGlobal('lightbox', lbox);
$('.modal-dialog').fadeIn();
lbox.connect();

function Box() {
  var self = this;
  self.id = '$id';
  self.auto_height = '$auto_height' == '1' ? true : false;
  self.auto_width = '$auto_width' == '1' ? true : false;
  self.width = 0;
  self.height = 0;
  self.lastWidth = 0;
  self.lastHeight = 0;

  /**
   * Sends a request to the Lightbox handler to resize the iframe.
   * The width and height are optional. If either are false the
   * dimension of the content will be used.
   */
  self.resize = function(width, height) {
    width = width || false;
    height = height || false;
    if (height === false) {
      if ($('pre').height() != null) {
        // adjust for height of debug messages
        height = parseFloat($('*[lightbox] .modal-dialog').height())
        + parseFloat($('pre').height())
        + parseFloat($('pre').css('padding-bottom').substring(0, $('pre').css('padding-bottom').length - 2))
        + parseFloat($('pre').css('padding-top').substring(0, $('pre').css('padding-top').length - 2))
        + parseFloat($('pre').css('margin-bottom').substring(0, $('pre').css('margin-bottom').length - 2));
      } else {
        height = parseFloat($('*[lightbox] .modal-dialog').height())
        + parseFloat($('*[lightbox] .modal-dialog').css('margin-bottom').substring(0, $('*[lightbox] .modal-dialog').css('margin-bottom').length - 2));
      }
    }
    if (width === false) {
      width = $('*[lightbox] .modal-dialog').width();
    }
    // store the new size
    self.lastWidth = self.width;
    self.lastHeight = self.height;
    self.width = width;
    self.height = height;

    // trigger resize
    parent.$(parent.window.document).trigger('lightbox.resize', {
      'id':self.id,
      'dimensions':{
        'height':height,
        'width':width
      }
    });
  }

  /**
   * Called by the Lightbox handler after this lightbox had been initialized.
   */
  self.acknowledge = function() {
    if (self.auto_height && self.auto_width) {
      self.resize();
    } else if (!self.auto_height && self.auto_width) {
      self.resize(false, '$height');
    } else if (self.auto_height && !self.auto_width) {
      self.resize('$width', false);
    } else {
      self.resize('$width', '$height');
    }
    // store the initial size
    self.original_width = self.width;
    self.original_height = self.height;
  }

  /**
   * Initiates the connection with the Lightbox handler
   */
  self.connect = function() {
    // bind dismiss
    $('*[data-dismiss]').click(function(e) {
      parent.$(parent.window.document).trigger('lightbox.dismiss', {
        'id':self.id,
        'callback': ''
      });
    });

    // bind submit
    $('button[type="submit"]').click(function() {
      // validate fields before submitting
      var valid = true;
      $('form').find('[required]').each(function(index, element) {
        if(!element.checkValidity()) {
          valid = false;
        }
      });
      if (valid) {
        $('form').submit();
      } else {
        $(this).button('reset');
        close_alerts();
        set_notice('Please fill in all required fields');
      }
    });

    // submit default configuration to parent (the lightbox handler)
    parent.$(parent.window.document).trigger('lightbox.init', {
      'id':self.id,
      'dimensions':{
        'height':'$height',
        'width':'$width'
      }
    });
  }
}
JAVASCRIPT;

        $request = explode('?', $_SERVER["REQUEST_URI"]);
        // default args
        $return_url = parameterize_url($this->request, '_lightbox_id', $this->id);
        $return_url = parameterize_url($return_url, '_returned', '1'); // used to identify when returning to a lightbox
        $lightbox = array(
            'id' => 'lightbox-' . $this->id,
            'height' => $this->height,
            'width' => $this->height,
            'header' => $this->header,
            'action' => $this->action,
            'method' => $this->method,
            'enctype' => $this->enctype,
            'lightbox_id' => $this->id,
            'return_url' => $return_url,
            'dismiss_url' => parameterize_url($return_url, '_dismiss', '1')
        );

        $args['lightbox'] = $lightbox;
        $args['body_class'] = 'body-as-lightbox';
        $args['_controller'] = 'lightbox';
        if (!isset($options['_controller'])) $options['_controller']['type'] = 'lightbox';
        return parent::renderView($view, $args, $options);
    }

    /**
     * Routes to a different page (most likely another lightbox)
     * while maintaining the token needed to communicate with the lightbox handler
     * @param bool|string $url the uri to which we will be routed.
     */
    protected function go($url = false) {
        if (!$url) {
            $url = $_SERVER['REQUEST_URI'];
        }
        $url = parameterize_url($url, '_lightbox_id', $this->id);
        parent::go($url);
    }
}