<?php

namespace atomar\core;

use atomar\Atomar;
use atomar\exception\UnknownController;
use atomar\hook\ServerResponseCode;
use atomar\hook\MaintenanceController;
use atomar\hook\MaintenanceRoute;
use atomar\hook\PostBoot;
use atomar\hook\PreBoot;
use atomar\hook\Route;

/**
 * Class Router handles all of the url routing
 * @package atomar
 */
class Router {
    /**
     * The url path that was requested by the browser
     * @var string
     */
    private static $_request_path = '';

    /**
     * The url query that was requested by the browser
     * @var string
     */
    private static $_request_query = '';

    /**
     * A stack to keep track of controllers processing the current url.
     * @var array
     */
    private static $controller_stack = array();

    /**
     * Tracks if the current url is a process or not
     * @var boolean
     */
    private static $is_process = false;

    /**
     * Tracks if the current url is a backend url
     * @var boolean
     */
    private static $is_backend = false;

    /**
     * Indicates that the Router has been initialized by init()
     * @var bool
     */
    private static $is_initialized = false;

    /**
     * Initializes the view manager
     */
    public static function init() {
        // set up request variables
        $regex = '^(?<path>((?!\?).)*)(?<query>.*)$';
        if (preg_match("/$regex/i", $_SERVER['REQUEST_URI'], $matches)) {
            self::$_request_path = $matches['path'];
            if (isset($matches['query'])) {
                self::$_request_query = $matches['query'];
            }
        }
        if (substr(self::$_request_path, 0, 3) == '/!/') {
            self::$is_process = true;
        }
        if (substr(self::$_request_path, 0, 7) == '/atomar') {
            self::$is_backend = true;
        }

        self::$is_initialized = true;
    }

    /**
     * Starts up the router
     * @param null|array $urls An array of urls to route. If left null the system will provide the default and hooked urls
     * @throws UnknownController
     * @throws \Exception
     */
    public static function run($urls = null) {
        if ($urls == null) {
            if(Atomar::get_system('maintenance_mode', '0') == '1' && !Auth::has_authentication('administer_site')) {
                $urls = Atomar::hook(new MaintenanceRoute());
            } else {
                $urls = Atomar::hook(new Route());
            }
        }

        Atomar::hook(new PostBoot());

        // begin routing
        try {
            self::route($urls);

            // show debugging info
            if (Auth::has_authentication('administer_site') && isset($_GET['debug'])) {
                if (Auth::$user) {
                    $user = Auth::$user->export();
                } else {
                    $user = array();
                }
                print_debug(array(
                    Atomar::$config,
                    $user,
                    $_SESSION,
                    fancy_date($_SESSION['last_activity']),
                    $urls
                ));
            }
        } catch (UnknownRouteException $e) {
            // url not found
            if(Auth::has_authentication('administer_site') || Atomar::$config['debug']) {
                $version = phpversion();
                echo Templator::render_template('@atomar/views/debug.html', array(
                    'e' => $e,
                    'body' => print_r($e, true),
                    'php_version' => $version
                ));
            } else {
                self::displayServerResponseCode(404);
            }
        } catch (\Exception $e) {
            // route error
            if(Auth::has_authentication('administer_site') || Atomar::$config['debug']) {
                $version = phpversion();
                echo Templator::render_template('@atomar/views/debug.html', array(
                    'e' => $e,
                    'body' => print_r($e, true),
                    'php_version' => $version
                ));
            } else {
                Logger::log_warning('Routing exception', $e->getMessage());
                self::displayServerResponseCode(500);
            }
        }
    }

    /**
     * Begins routing to the appropriate classes
     *
     * @param   array $urls The regex-based url to class mapping
     * @throws \Exception|UnknownRouteException
     */
    private static function route($urls) {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        $path = $_SERVER['REQUEST_URI'];

        krsort($urls);

        // match route
        foreach ($urls as $regex => $class) {
            $regex = str_replace('/', '\/', $regex);
            $regex = '^' . $regex . '\/?$';
            if (preg_match("/$regex/i", $path, $matches)) {
                if (class_exists($class)) {
                    $controller = new $class(false);
                    self::callController($controller, $method, $matches);
                } else {
                    throw new \Exception("Class, $class, not found.");
                }
                return;
            }
        }

        // handle maintenance mode
        if(Atomar::get_system('maintenance_mode', '0') == '1') {
            $controller = Atomar::hook(new MaintenanceController());
            self::callController($controller, $method, array());
            return;
        }

        // un-matched route
        throw new UnknownRouteException("URL, $path, not found.");
    }

    /**
     * Displays a server response code in the browser and halts execution.
     * This is usually used to display error codes.
     *
     * @param int $code
     */
    public static function displayServerResponseCode(int $code) {
        $controller = Atomar::hook(new ServerResponseCode($code));
        http_response_code($code);
        if($controller instanceof Controller) {
            $controller->GET(array('code' => $code));
        } else {
            // in the rare event there is no controller just print the code
            echo $code;
        }
        exit();
    }

    /**
     * Executes a controller.
     * @param Controller $controller the controller to run
     * @param string $method the method to be called
     * @param array $matches the matched arguments found in the url
     * @throws \BadMethodCallException if the request method is not implemented in the controller
     */
    private static function callController($controller, $method, $matches=array()) {
        if($controller instanceof Controller && method_exists($controller, $method)) {
            try {
                $controller->$method($matches);
            } catch (\Exception $e) {
                $controller->exception_handler($e);
            }
        } else {
            throw new \BadMethodCallException("Method, $method, not supported on " . get_class($controller) . ".");
        }
    }

    /**
     * Checks if the url is the current address
     * @param string $uri The url to check
     * @param bool $exact If true the url must match exactly e.g. sub pages will not match
     * @return bool
     */
    public static function is_active_url($uri, $exact = false) {
        $uri = trim($uri, '/');
        $parts = explode('/', trim(self::request_path(), '/'));
        if ($uri == trim(self::request_path(), '/') || $uri == trim(self::request_path() . self::request_query(), '/')) {
            return true;
        } else if (!$exact) {
            $count = count($parts);
            while ($count > 0) {
                if ($uri == implode('/', $parts)) {
                    return true;
                } else {
                    unset($parts[count($parts) - 1]);
                    $count = count($parts);
                }
            }
            return false;
        }
    }

    /**
     * Returns the path component of the request
     * @return string
     */
    public static function request_path() {
        return self::$_request_path;
    }

    /**
     * Returns the query component of the request
     * @return string
     */
    public static function request_query() {
        return self::$_request_query;
    }

    /**
     * Returns the fully qualified url of the current page
     * @return string
     */
    public static function page_url() {
        return rtrim(Atomar::$config['site_url'], '/') . '/' . ltrim(self::request_path(), '/') . self::request_query();
    }

    /**
     * Redirects to a new url.
     *
     * @param string $url the url that the site will navigate to
     */
    public static function go($url) {
        header('Location: ' . $url);
        exit(1);
    }

    /**
     * Check if the current url is executing a process.
     * TODO: technically these are rest APIs not processes.
     * @return boolean true if the url is a process
     */
    public static function is_url_process() {
        return self::$is_process;
    }

    /**
     * Push a controller onto the controller stack
     * @param string $controller the name of the controller instance
     */
    public static function push_controller($controller) {
        self::$controller_stack[] = $controller;
    }

    /**
     * Pops the last controller off of the controller stack
     * @return string the name of the controller instance
     */
    public static function pop_controller() {
        return array_pop(self::$controller_stack);
    }

    /**
     * Returns the controller most recently pushed onto the stack.
     * @return string the name of the current controller instance
     */
    public static function current_controller() {
        return end(self::$controller_stack);
    }

    /**
     * Check if the current url is a backend url a.k.a /atomar
     * @return boolean true if the url is a backend url.
     */
    public static function is_url_backend() {
        return self::$is_backend;
    }
}

class UnknownRouteException extends \Exception {

}