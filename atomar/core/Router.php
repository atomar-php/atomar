<?php

namespace atomar\core;

use atomar\Atomar;
use atomar\exception\UnknownController;
use atomar\hook\PostProcessBoot;
use atomar\hook\PreProcessBoot;
use atomar\hook\Url;

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
        if (substr(self::$_request_path, 0, 6) == '/atomar') {
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

            /**
             * CONTROLLER
             */
            // map urls to controllers
            $system_urls = array(
                '/404/?(\?.*)?' => 'atomar\controller\ExceptionHandler',
                '/user/reset/?(\?.*)?' => 'atomar\controller\UserReset',
            );
            $authenticated_urls = array(
                '/user/logout/?(\?.*)?' => 'atomar\controller\UserLogout',
                '/user/(?P<id>\d+)/?(\?.*)?' => 'atomar\controller\User',
                '/atomar/user/(?P<id>\d+)/edit/?(\?.*)?' => 'atomar\controller\AdminUserEdit',
                '/atomar/?(\?.*)?' => 'atomar\controller\Admin',
                '/atomar/users/?(\?.*)?' => 'atomar\controller\AdminUsers',
                '/atomar/users/create/?(\?.*)?' => 'atomar\controller\AdminUsersCreate',
                '/atomar/permissions/?(\?.*)?' => 'atomar\controller\AdminPermissions',
                '/atomar/roles/?(\?.*)?' => 'atomar\controller\AdminRoles',
                '/atomar/roles/create/?(\?.*)?' => 'atomar\controller\AdminRolesCreate',
                '/atomar/roles/(?P<id>\d+)/edit/?(\?.*)?' => 'atomar\controller\AdminRolesEdit',
                '/atomar/configuration/?(\?.*)?' => 'atomar\controller\AdminConfiguration',
                '/atomar/documentation/?(\?.*)?' => 'atomar\controller\AdminDocumentation',
                '/atomar/documentation/(?P<type>[a-z]+)/(?P<name>[a-zA-Z\.\_\-]+)/?(\?.*)?' => 'atomar\controller\AdminDocumentation',
                '/atomar/extensions/?(\?.*)?' => 'atomar\controller\AdminExtensions',
                '/atomar/settings/?(\?.*)?' => 'atomar\controller\AdminSettings',
                '/atomar/extensions/new/?(\?.*)?' => 'atomar\controller\AdminExtensionsNew',
                '/atomar/migrations/new/?(\?.*)?' => 'atomar\controller\AdminMigrationsNew',
                '/atomar/performance/?(\?.*)?' => 'atomar\controller\AdminPerformance',
            );
            $unauthenticated_urls = array(
                '/user/login/?(\?.*)?' => 'atomar\controller\UserLogin',
            );
            $public_urls = array(
                '/!/(?P<api>[a-zA-Z\_-]+)/?(\?.*)?' => 'atomar\controller\API',
                '/(\?.*)?' => 'atomar\controller\Index'
            );
            $maintenance_urls = array(
                '/(\?.*)?' => 'atomar\controller\Maintenance'
            );

            // validate cache and files path path
            if (Auth::has_authentication('administer_site')) {
                if (!file_exists(Atomar::$config['cache'])) {
                    mkdir(Atomar::$config['cache'], 0775, true);
                }
                if (!file_exists(Atomar::$config['files'])) {
                    mkdir(Atomar::$config['files'], 0770, true);
                }
                if (!is_writable(Atomar::$config['cache'])) {
                    set_error('The cache directory (' . Atomar::$config['cache'] . ') is not writeable');
                }
                if (!is_writable(Atomar::$config['files'])) {
                    set_error('The files directory (' . Atomar::$config['files'] . ') is not writeable');
                }
            }

            if (Auth::$user) {
                // give the user info to js
                $fname = str_replace('\'', '\\\'', Auth::$user->first_name);
                $lname = str_replace('\'', '\\\'', Auth::$user->last_name);
                $id = Auth::$user->id;
                Templator::$js_onload[] = <<<JAVASCRIPT
var user = {
  first_name:'$fname',
  last_name:'$lname',
  id:$id
}
if(typeof RegisterGlobal == 'function') RegisterGlobal('user', user);
JAVASCRIPT;
            }

            /**
             * Enable appropriate urls
             */
            if (Atomar::get_system('maintenance_mode', '0') == '1' && !Auth::is_super() && !Auth::is_admin() && !Auth::has_authentication('skip_maintenance_mode')) {
                $extension_urls = Atomar::hook(new Url());
                // extensions may only override system and unauthenticated urls while in maintenance mode
                $overidable_system_urls = array_intersect_key($extension_urls, $system_urls);
                $overidable_unauthenticated_urls = array_intersect_key($extension_urls, $unauthenticated_urls);
                if(!Auth::$user) {
                    $urls = array_merge($unauthenticated_urls);
                } else {
                    $urls = array();
                }
                $urls = array_merge($urls, $maintenance_urls, $system_urls, $authenticated_urls, $overidable_system_urls, $overidable_unauthenticated_urls);
            } else {
                if (!Auth::$user) {
                    // require login
                    $urls = array_merge($public_urls, $system_urls, $unauthenticated_urls);
                } else {
                    // authenticated
                    $urls = array_merge($public_urls, $system_urls, $authenticated_urls);
                }
                try {
                    $extension_urls = Atomar::hook(new Url());
                    if (is_array($extension_urls)) {
                        $urls = array_merge($urls, $extension_urls);
                    }

                } catch (UnknownController $e) {
                    if (Atomar::$config['debug']) {
                        // TODO: perform some automated tasks to facilitate development
                        throw $e;
                    }
                }
            }
            unset($public_urls, $system_urls, $unauthenticated_urls, $authenticated_urls);

            Atomar::hook(new PostProcessBoot());
        }

        // begin routing
        try {
            self::route($urls);
            /**
             * Display debugging info
             */
            if ((Auth::is_super() || Auth::is_admin()) && Atomar::$config['debug'] && isset($_GET['debug']) && $_GET['debug'] == 1) {
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
        } catch (\Exception $e) {
            if(Atomar::$config['debug']) {
                Logger::log_warning('Routing exception', $e->getMessage());
            }
            $path = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            if (Atomar::get_system('maintenance_mode', '0') == '1' && !Auth::is_super() && !Auth::is_admin() && !Auth::has_authentication('skip_maintenance_mode')) {
                // prevent redirect loops.
                if (!self::is_active_url('/', true)) {
                    self::go('/');
                } else {
                    self::redirect_loop_catcher($path);
                }
            } else if (Atomar::$config['debug'] || Auth::is_super() || Auth::is_admin()) {
                // print the error
                $version = phpversion();
                echo Templator::render_view('debug.html', array(
                    'exception' => $e,
                    'php_version' => $version
                ));
            } else if (!Auth::$user) {
                // un-authenticated users
                Logger::log_error('An exception occurred in the controller while on the route ' . $path, $e->getMessage());
                if (!self::is_active_url('/', true)) {
                    self::go('/');
                } else {
                    self::redirect_loop_catcher($path);
                }
            } else {
                if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
                    $scheme = 'https://';
                } else {
                    $scheme = 'http://';
                }
                $path = $scheme . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                echo Templator::render_view('404.html', array(
                    'path' => $path
                ));
            }
        }
    }

    /**
     * Begins routing to the appropriate classes
     *
     * @param   array $urls The regex-based url to class mapping
     * @throws \Exception Thrown if no match is found
     */
    private static function route($urls) {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        $path = $_SERVER['REQUEST_URI'];

        $found = false;

        krsort($urls);

        foreach ($urls as $regex => $class) {
            $regex = str_replace('/', '\/', $regex);
            $regex = '^' . $regex . '\/?$';
            if (preg_match("/$regex/i", $path, $matches)) {
                $found = true;
                if (class_exists($class)) {
                    $obj = new $class(false);
                    if (method_exists($obj, $method)) {
                        try {
                            $obj->$method($matches);
                        } catch (\Exception $e) {
                            if (method_exists($obj, 'exception_handler')) {
                                $obj->exception_handler($e);
                            } else {
                                throw $e;
                            }
                        }
                    } else {
                        throw new \BadMethodCallException("Method, $method, not supported.");
                    }
                } else {
                    throw new \Exception("Class, $class, not found.");
                }
                break;
            }
        }
        if (!$found) {
            throw new \Exception("URL, $path, not found.");
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
     * Display a 404 page instead of starting a redirect loop
     * @param $path
     */
    private static function redirect_loop_catcher($path) {
        Logger::log_warning('Detected a potential redirect loop', $path);
        echo Templator::render_view('500.html');
        exit;
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