<?php

namespace atomar\core;

/**
 * The controller is initialized and ran at the end of a url route.
 */
abstract class Controller {
    private $return_url = false;

    function __construct($return_url = false) {
        // a custom return path may be defined to easily route to the previous uri

        if ($return_url) {
            $this->return_url = $return_url;
            $_SESSION['return'] = $return_url;
        } elseif (isset($_REQUEST['r'])) {
            $this->return_url = $_REQUEST['r'];
            $_SESSION['return'] = $_REQUEST['r'];
        } elseif (isset($_SERVER['HTTP_REFERER'])) {
            $this->return_url = $_SERVER['HTTP_REFERER'];
            $_SESSION['return'] = $_SERVER['HTTP_REFERER'];
        } else {
            unset($_SESSION['return']);
        }
        // push controller onto the stack
        Router::push_controller(get_class($this));
    }

    /**
     * Process GET requests
     * @param array $matches the matched patterns from the route
     */
    abstract function GET($matches = array());

    /**
     * Process POST requests
     * @param array $matches the matched patterns from the route
     */
    abstract function POST($matches = array());

    /**
     * Route to the return uri.
     * @param string|null $default_url If all else fails route to this url
     * @throws \Exception
     */
    protected function go_back($default_url = null) {
        if($this->return_url) {
            $url = $this->return_url;
        } else {
            $url = $default_url;
        }
        // prevent loops
        if (is_string($url) && !Router::is_active_url($url, true)) {
            Router::go($url);
        } else if (!Router::is_active_url('/', true)) {
            Router::go('/');
        } else {
            // we hit a redirect loop
            Logger::log_warning('Detected a potential redirect loop', Router::request_path());
            echo Templator::render_view('500.html');
            exit;
        }
    }

    /**
     * Returns the current return url
     * @return string
     */
    protected function get_return_url() {
        return $this->return_url ? $this->return_url : '/';
    }

    /**
     * Renders the view and does some extra processing
     * @param string $view the relative path to the view that will be redered
     * @param array $args custom options that will be sent to the view
     * @param array $options optional rules regarding how the template will be rendered.
     * @return string the rendered html
     */
    protected function render_view($view, $args = array(), $options = array()) {
        if (!isset($options['_controller'])) $options['_controller']['type'] = 'controller';
        return Templator::render_view($view, $args, $options);
    }

    /**
     * Routes to a different uri and does some extra processing.
     * @param String|bool $url uri that we will be routed to.
     */
    protected function go($url = false) {
        // TODO: need to decide if we actually want to set the return url each time.
        // provide a default return uri
        // $_SESSION['return'] = $_SERVER["REQUEST_URI"];
        // Reload the current page by default
        if (!$url) {
            $url = $_SERVER['REQUEST_URI'];
        }
        // NOTE: the redirect loop detection does not work correctly so it is disabled.
//      if(!l_active($url, true) || $_SESSION['system:last_route'] != $url) {
        Router::go($url);
//      } else {
//        // we hit a redirect loop
//        log_warning('Detected a potential redirect loop', S::$_REQUEST_PATH);
//        echo render_view('500.html');
//        exit;
//      }
    }

    /**
     * Renders a 404 error on the current page.
     */
    protected function throw404() {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $scheme = 'https://';
        } else {
            $scheme = 'http://';
        }
        $path = $scheme . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        echo Templator::render_view('404.html', array(
            'path' => $path
        ));
        exit(1);
    }
}