<?php

namespace atomar\core;

use atomar\Atomar;

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
            $this->throw500();
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
     * @param string $view the relative path to the view that will be rendered
     * @param array $args custom options that will be sent to the view
     * @param array $options optional rules regarding how the template will be rendered.
     * @return string the rendered html
     */
    protected function renderView($view, $args = array(), $options = array()) {
        if (!isset($options['_controller'])) $options['_controller']['type'] = 'controller';
        return Templator::renderView($view, $args, $options);
    }

    /**
     * Routes to a different uri and does some extra processing.
     * @param String|bool $url uri that we will be routed to.
     */
    protected function go($url = false) {
        // Reload the current page by default
        if (!$url) {
            $url = $_SERVER['REQUEST_URI'];
        }
        Router::go($url);
    }

    protected function throw404() {
        Router::displayServerResponseCode(404);
    }

    protected function throw500() {
        Router::displayServerResponseCode(500);
    }

    /**
     * This method will be called automatically to handle any exceptions in the controller.
     *
     * @param \Exception $e the exception
     */
    public function exception_handler($e) {
        if(Atomar::$config['debug']) {
            $version = phpversion();
            echo Templator::render_template("@atomar/views/debug.html", array(
                'e' => $e,
                'body' => print_r($e, true),
                'php_version' => $version
            ));
            exit(1);
        } else {
            Logger::log_error($e->getMessage(), $e);
            $this->throw500();
        }
    }
}