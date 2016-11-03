<?php

namespace atomar;

use atomar\core\AssetManager;
use atomar\core\Auth;
use atomar\core\AutoLoader;
use atomar\core\Logger;
use atomar\core\ReadOnlyArray;
use atomar\core\Router;
use atomar\core\Templator;
use atomar\hook\Hook;
use atomar\hook\Libraries;
use atomar\hook\PreProcessBoot;
use model\Extension;

require_once(__DIR__ . '/core/AutoLoader.php');

/**
 * Class Atomar is the center of the Atomar framework.
 * @package atomar
 */
class Atomar {

    /**
     * The site configuration.
     * @var \atomar\core\ReadOnlyArray
     */
    public static $config;
    /**
     * Embodies the site menu system.
     * TODO: the menu should be placed in it's own class
     * You may modify this array at any time using the appropriate design pattern.
     * You can see here we started with two empty menus. You may add as many menus as nessesary
     * and they will be rendered wherever they are called in the template.
     * @var array
     */
    public static $menu = array(
        'primary_menu' => array(
            'class' => array(
                'nav',
                'navbar-nav'
            )
        ),
        'secondary_menu' => array(
            'class' => array(
                'nav',
                'nav-list',
                'dropdown'
            )
        )
    );
    /**
     * The manifest information
     * @var \atomar\core\ReadOnlyArray
     */
    private static $manifest;
    /**
     * Indicates that the Atomar has been initialized by init()
     * @var bool
     */
    private static $is_initialized = false;

    /**
     * The function that will be executed when maintenance mode is active
     * @var \Closure
     */
    private static $_maintenance_mode_callback = null;

    /**
     * The site application
     * @var null|\RedBeanPHP\OODBBean
     */
    private static $app;

    /**
     * Initializes the system.
     * Parameters may be passed directly as a map or you may specify a configuration file
     * @param string $config_path mixed the path to the site configuration or a map of values
     * @throws \Exception
     */
    public static function init(string $config_path) {
        AutoLoader::register(self::atomar_dir(), 1);
        AutoLoader::register(__DIR__ . '/vendor');

        // MVC
        require_once(__DIR__ . '/vendor/red_bean/rb.php');
        require_once(__DIR__ . '/vendor/Twig/Autoloader.php');
        require_once(__DIR__ . '/vendor/Twig/SimpleFunction.php');

        require_once(__DIR__ . '/core/functions.php');

        // load the configuration
        if (is_string($config_path)) {
            if (file_exists($config_path)) {
                self::$config = new ReadOnlyArray(json_decode(file_get_contents($config_path), true));
            } else {
                throw new \Exception("configuration file is missing");
            }
        } elseif (is_array($config_path)) {
            self::$config = new ReadOnlyArray($config_path);
        } else {
            throw new \Exception("invalid configuration argument");
        }

        // load manifest
        self::$manifest = new ReadOnlyArray(json_decode(file_get_contents(__DIR__ . '/atomar.json'), true));

        if(!is_dir(self::$config['ext_dir'])) {
            mkdir(self::$config['ext_dir'], 0775);
        }

        self::$is_initialized = true;
    }

    /**
     * Returns the path to the root atomar dir
     * @deprecated use atomar_dir instead
     * @return string
     */
    public static function root_dir() {
        return __DIR__;
    }

    /**
     * Returns the path to the root atomar dir
     * @return string
     */
    public static function atomar_dir() {
        return __DIR__;
    }

    /**
     * Returns the path to the root site dir
     * @return string
     */
    public static function site_dir() {
        return dirname($_SERVER["SCRIPT_FILENAME"]);
    }

    /**
     * Returns the information regarding the application
     * @return array
     */
    public static function getAppInfo() {
        return self::$app ? self::$app->export() : null;
    }

    /**
     * Starts up the system!
     * You may optionally specify what app directory to run
     * otherwise the one specified in the config will be used.
     * @param string $app_path An optional path to the application directory
     * @throws \Exception
     */
    public static function run(string $app_path = null) {
        if (self::$is_initialized) {
            if (is_string($app_path)) {
                self::$config['app_dir'] = $app_path;
            } elseif ($app_path != null) {
                throw new \Exception("invalid configuration argument. Expected a string or null");
            }
            self::boot();
        } else {
            throw new \Exception("the core has not been initialized yet. Please run init() first");
        }
    }

    /**
     * Boots up the application
     */
    private static function boot() {
        Templator::init();
        Router::init();

        /**
         * Server static assets
         */
        // TODO: make this configurable so modules can define static directories
        AssetManager::run();

        /**
         * Model
         */
        // prefix model names. e.g. MUser
        define('REDBEAN_MODEL_PREFIX', '\\model\\');
        // set up connection
        $db = self::$config['db'];
        \R::setup('mysql:host=' . $db['host'] . ';dbname=' . $db['name'], $db['user'], $db['password']);
        // test db connection
        if (!\R::testConnection()) {
            $message = <<<HTML
  <p>
    A connection to the database could not be made. Double check your configuration and try again.
  </p>
HTML;
            echo Templator::render_error('DB Connection Failed', $message);
            exit(1);
        }
        unset($db);

        /**
         * Read debug mode
         *
         */
        // enable debug by default
        self::$config['debug'] = boolval(self::get_system('debug', true));
        self::$config->lock();

        if (!self::$config['debug']) {
            \R::useWriterCache(true);
            // TODO: breaks adding certain things to the db
            \R::freeze(true);
            error_reporting(0);
        } else {
            ini_set('display_errors', 1);
            error_reporting(E_ALL ^ E_NOTICE);
        }

        /**
         * AUTHENTICATION
         */
        Auth::setup(self::$config['auth']);
        Auth::run();

        /**
         * PHP Info
         */
        if (self::$config['debug'] && isset($_REQUEST['phpinfo']) && $_REQUEST['phpinfo'] && Auth::has_authentication('administer_site')) {
            phpinfo();
            exit;
        }

        /**
         * Check for pending updates for this version if the currently logged in user is the super user.
         */
        if (Auth::has_authentication('administer_site')) {
            self::version_check();
        }

        /**
         * Default Maintenance handler
         *
         */
        self::set_maintenance_mode_handler(function () {
            $site_name = Atomar::$config['site_name'];
            $message = <<<HTML
  <p class="text-center">
     $site_name is currently being updated and will be back online shortly.
  </p>
HTML;
            echo Templator::render_error('Site Maintenance', $message);
            exit;
        });

        /**
         * Check if installation is required
         */
        if (!Auth::$user) {
            if (count(\R::findAll('user')) == 0) {
                $urls = array(
                    '/.*' => 'atomar\controller\Install',
                );
                Router::run($urls);
                exit;
            }
        }

        /**
         * Autoload extensions
         *
         */
        $extensions = \R::find('extension', 'is_enabled=\'1\'');
        foreach ($extensions as $ext) {
            AutoLoader::register(realpath(self::extension_dir() . $ext->slug));
        }

        /**
         * Autoload app
         *
         */
        AutoLoader::register(self::application_dir());
        self::$app = self::loadModule(self::application_dir(), self::application_namespace());
        if (!isset(self::$app)) {
            Logger::log_error('Could not load the application from ' . self::application_dir());
            if (self::$config['debug']) {
                set_error('Failed to load the application');
            }
        } else {
            self::$app->installed_version = self::get_system('app_installed_version', 0);
            if (vercmp(self::$app->version, self::$app->installed_version) > 0) {
                self::$app->is_update_pending = '1';
            }
            // restrict usage of application if it specifies a supported version of atomar
            if (isset(self::$app->atomar_version) && vercmp(self::$app->atomar_version, self::version()) < 0) {
                self::$app = null;
                Logger::log_error('The application does not support this version of Atomar. Found ' . self::$app->version . ' but expected at least ' . self::version());
                if (self::$config['debug']) {
                    set_error('The application does not support this version of Atomar.');
                }
            }
        }

        self::hook(new PreProcessBoot());

        /**
         * VIEW
         */
        \Twig_Autoloader::register();

        self::hook(new Libraries());

        Router::run();
    }

    /**
     * Checks if the code has been updated.
     *
     */
    public static function version_check() {
        // check atomar version.
        $version = self::get_system('version', self::version());
        if ($version != self::version()) {
            // check for new version.
            if (vercmp($version, self::version()) == -1) {
                // Upgrade
                self::hook_core_update($version, self::version());
            } elseif (vercmp($version, self::version()) == 1) {
                // Downgrade
                $msg = 'Atomar has been downgraded to version ' . self::version();
                set_notice($msg);
                Logger::log_notice($msg);
                Atomar::set_system('version', self::version());
            }
        }
    }

    /**
     * Returns the current version of the atomar core.
     *
     * @return string The site version.
     */
    public static function version() {
        return self::$manifest['version'];
    }

    /**
     * This is a special private hook that performs operations to migrate an older version of the core to a newer version.
     * @param string $from the old version we are updating from
     * @param string $to the new version we are updating to
     * @throws \Exception
     */
    private static function hook_core_update(string $from, string $to) {
        $from = trim($from);
        $to = trim($to);
        $migration_dir = __DIR__ . '/migration/';

        // Perform all the migrations between $from and $to.
        $migration_source = $from;
        $migration_target = $to;
        do {
            // locate exact migration match
            $migration_file = $migration_source . '_*.php';
            $files = glob($migration_dir . $migration_file, GLOB_MARK | GLOB_NOSORT);
            if (count($files) > 1) {
                throw new \LengthException('Multiple migration paths found. There may only be one migration path. Expression: ' . $migration_file);
            } else {
                if (count($files) == 0) {
                    // locate next available migration
                    $files = glob($migration_dir . '*.php', GLOB_MARK);
                    foreach ($files as $file) {
                        preg_match('/(?P<source>[\.\d]+)\_(?P<target>[\.\d]+)+/', rtrim(basename($file), '.php'), $matches);
                        // if file_source >= migration_source && file_target <= $to
                        $target_to_compare = vercmp($matches['target'], $to);
                        if (vercmp($matches['source'], $migration_source) != -1 && $target_to_compare < 1) {
                            // skip to the next available migration
                            self::hook_core_update($matches['source'], $to);
                            break;
                        } elseif ($target_to_compare == 1) {
                            // no migrations within this upgrade
                            // TODO: not sure if I need this here
                            $msg = 'Atomar has been updated to version ' . self::version();
                            set_notice($msg);
                            Logger::log_notice($msg);
                            Atomar::set_system('version', self::version());
                            break;
                        }
                    }
                    // there are no migration files
                    $msg = 'Atomar has been updated to version ' . self::version();
                    set_notice($msg);
                    Logger::log_notice($msg);
                    Atomar::set_system('version', self::version());
                    break;
                }
                $file = $files[0];
                // execute the migration file
                $class = 'migration_' . md5(rtrim(basename($file), '.php'));
                preg_match('/[\.\d]+\_(?P<target>[\.\d]+)/', rtrim(basename($file), '.php'), $matches);
                $migration_target = $matches['target'];
                require_once($file);
                if (class_exists($class)) {
                    $obj = new $class();
                    if (method_exists($obj, 'run')) {
                        if ($obj->run()) {
                            set_success('The migration from ' . $migration_source . ' to ' . $migration_target . ' is complete');
                            $migration_source = $migration_target;
                            // Update version
                            $msg = 'Atomar has been updated to version ' . $migration_target;
                            Logger::log_notice($msg);
                            Atomar::set_system('version', $migration_target);
                        } else {
                            set_error('The migration from ' . $migration_source . ' to ' . $migration_target . ' failed.');
                            break;
                        }
                    } else {
                        throw new \BadMethodCallException('Method, run, not supported.');
                    }
                } else {
                    Logger::log_error('The migration failed from ' . $from . ' to ' . $to, 'Misconfigured migration file in ' . $file . '. Missing migration class "' . $class . '". ');
                    throw new \Exception('Misconfigured migration file in ' . $file . '. Missing migration class "' . $class . '"');
                }
            }
        } while ($migration_target !== $to);
    }

    /**
     * Allows the default maintenance mode callback to be overridden.
     * @param \Closure $function the callback to execute when maintenance mode is active.
     */
    public static function set_maintenance_mode_handler(\Closure $function) {
        self::$_maintenance_mode_callback = $function;
    }

    /**
     * Returns the path to the extension directory
     * @return string
     */
    public static function extension_dir() {
        return self::$config['ext_dir'];
    }

    /**
     * Returns the path to the application directory
     * @return string
     */
    public static function application_dir() {
        return rtrim(self::$config['app_dir'], '/') . '/';
    }

    /**
     * Loads an extension from a directory
     * @param string $path The path to the extension directory
     * @param string $slug The extension slug
     * @return null|\RedBeanPHP\OODBBean
     */
    public static function loadModule(string $path, string $slug) {
        if (is_dir($path)) {
            $manifest_file = rtrim($path, '/') . '/atomar.json';
            if (file_exists($manifest_file)) {
                $manifest = json_decode(file_get_contents($manifest_file), true);

                // find existing extension
                $ext = \R::findOne('extension', 'slug=? LIMIT 1', array($slug));

                if (!$ext) {
                    // create new extension
                    $ext = \R::dispense('extension');
                    $ext->slug = $slug;
                    $ext->is_enabled = '0';
                    $ext->is_update_pending = '0';
                }

                // load updated info
                $ext->import($manifest, 'name,description,version,atomar_version,author');
                if(!isset($manifest['atomar_version'])) {
                    $ext->atomar_version = null;
                }
                if (isset($manifest['dependencies'])) {
                    $ext->set_dependencies($manifest['dependencies']);
                }
                try {
                    \R::store($ext);
                } catch(\Exception $e) {
                    Logger::log_error("Failed to load the module " + $slug, $e);
                    return null;
                }

                return $ext->box();
            }
        }
        return null;
    }

    /**
     * Performs operations on a hook
     * @param Hook $hook the hook to perform
     * @return mixed
     */
    public static function hook(Hook $hook) {
        // execute hooks on extensions
        $extensions = \R::find('extension', 'is_enabled=\'1\'');
        $hook_name = strtolower(preg_replace('/(?<!^)([A-Z])/', '_$1', ltrim(strrchr(get_class($hook), '\\'), '\\')));
        $state = array();
        foreach ($extensions as $ext) {
            $fun = $ext->slug . '\\' . $hook_name;
            $ext_dir = self::extension_dir() . $ext->slug;
            $ext_hooks_path = $ext_dir . DIRECTORY_SEPARATOR . 'hooks.php';
            try {
                if (file_exists($ext_hooks_path)) {
                    include_once($ext_hooks_path);
                } else {
                    Logger::log_error('Missing hooks file: ' . $ext_hooks_path);
                    continue;
                }
            } catch (\Exception $e) {
                Logger::log_error('Could not include file: ' . $ext_hooks_path, $e->getMessage());
                continue;
            }
            if (function_exists($fun)) {
                $hook->pre_process($fun, $ext);
                $state = $hook->process(call_user_func($fun), self::extension_dir() . $ext->slug . DIRECTORY_SEPARATOR, $ext->slug, $ext, $state);
            }
        }

        // execute hook on application
        if (self::$app != null) {
            $fun = self::application_namespace() . '\\' . $hook_name;
            $app_hooks_path = self::application_dir() . 'hooks.php';
            try {
                if (file_exists($app_hooks_path)) {
                    include_once($app_hooks_path);
                } else {
                    Logger::log_error('Missing application hooks file: ' . $app_hooks_path);
                }
            } catch (\Exception $e) {
                Logger::log_error('Could not include file: ' . $app_hooks_path, $e->getMessage());
            }
            if (function_exists($fun)) {
                $hook->pre_process($fun, self::$app);
                $state = $hook->process(call_user_func($fun), self::application_dir(), self::application_namespace(), self::$app, $state);
            } else if (self::$config['debug']) {
                set_warning('Could not find ' . $fun . '. Make sure you have configured the application directory and namespace in the configuration file.');
            }
        }
        return $hook->post_process($state);
    }

    /**
     * Returns the namespace of the application directory
     * @return string
     */
    public static function application_namespace() {
        return self::$config['app_namespace'];
    }

    /**
     * Executes the maintenance mode callback
     * By default all template hooks and options are disabled
     */
    public static function run_maintenance() {
        if (is_callable(self::$_maintenance_mode_callback)) {
            $options['_controller']['type'] = 'controller';
            $options['render_messages'] = false;
            $options['render_menus'] = false;
            $options['trigger_preprocess_page'] = false;
            $options['trigger_twig_function'] = false;
            $options['trigger_menu'] = false;
            call_user_func(self::$_maintenance_mode_callback, array(), $options);
            exit;
        } else {
            throw new \Exception('The site is currently being updated.', 1);
        }
    }

    /**
     * A hook to run installation procedures after an extension has been enabled
     */
    public static function install_extensions() {
        $extensions = \R::find('extension', ' is_enabled=\'1\' AND version<>installed_version ');
        foreach ($extensions as $ext) {

            // install function
            $ext_path = self::$config['ext_dir'] . $ext->slug . '/install.php';
            try {
                include_once($ext_path);
            } catch (\Exception $e) {
                Logger::log_warning('Could not load the installation file for extension ' . $ext->slug, $e->getMessage());
                continue;
            }

            $file = file_get_contents($ext_path);
            $matches = array();
            if (preg_match_all('(update_[\d\_]+)', $file, $matches)) {
                $methods = array_flip($matches[0]);
                ksort($methods);
                if (!$ext->installed_version) {
                    $ext->installed_version = 0;
                }

                $errors = false;
                foreach ($methods as $fun => $value) {
                    $matches = array();
                    if (preg_match('/^update_([\d\_]+)$/', $fun, $matches)) {
                        $version = str_replace('_', '.', $matches[1]);
                        $max_version_compare = vercmp($version, $ext->version);
                        if (vercmp($version, $ext->installed_version) == 1 && ($max_version_compare == 0 || $max_version_compare == -1)) {
                            // double check in case we accidentally picked up commented code.
                            if (function_exists($ext->slug . '\\' . $fun)) {
                                if (call_user_func($ext->slug . '\\' . $fun)) {
                                    if ($version == $ext->version) {
                                        // we are done so display success notice.
                                        set_success('Updated extension ' . $ext->slug . ' to version ' . $ext->version);
                                    }
                                    $ext->installed_version = $version;
                                    store($ext);
                                } else {
                                    // stop running updates for this extension.
                                    set_error('Failed to process update ' . $version . ' for extension ' . $ext->slug);
                                    $errors = true;
                                    break;
                                }
                            }
                        }
                    }
                }

                // display success notice
                if (!$errors) {
                    set_success('Updated extension ' . $ext->slug . ' to version ' . $ext->version);
                    $ext->installed_version = $ext->version;
                    store($ext);
                }
            }
        }
    }

    /**
     * Performs install/update procedures on the application
     */
    public static function install_application() {
        if (self::$app != null) {
            $app_install_path = self::application_dir() . 'install.php';
            try {
                if (file_exists($app_install_path)) {
                    include_once($app_install_path);
                } else {
                    Logger::log_error('Missing application install file: ' . $app_install_path);
                }
            } catch (\Exception $e) {
                Logger::log_error('Could not include file: ' . $app_install_path, $e->getMessage());
            }

            $file = file_get_contents($app_install_path);
            $matches = array();
            if (preg_match_all('(update_[\d\_]+)', $file, $matches)) {
                $methods = array_flip($matches[0]);
                ksort($methods);
                if (!self::$app->installed_version) {
                    self::$app->installed_version = 0;
                }

                $errors = false;
                foreach ($methods as $fun => $value) {
                    $matches = array();
                    if (preg_match('/^update_([\d\_]+)$/', $fun, $matches)) {
                        $version = str_replace('_', '.', $matches[1]);
                        $max_version_compare = vercmp($version, self::$app->version);
                        if (vercmp($version, self::$app->installed_version) == 1 && ($max_version_compare == 0 || $max_version_compare == -1)) {
                            // double check in case we accidentally picked up commented code.
                            if (function_exists(self::application_namespace() . '\\' . $fun)) {
                                if (call_user_func(self::application_namespace() . '\\' . $fun)) {
                                    if ($version == self::$app->version) {
                                        // we are done so display success notice.
                                        set_success('Updated application to version ' . self::$app->version);
                                    }
                                    self::$app->installed_version = $version;
                                    Atomar::set_system('app_installed_version', $version);
                                } else {
                                    // stop running updates for this extension.
                                    set_error('Failed to process update ' . $version . ' for application');
                                    $errors = true;
                                    break;
                                }
                            }
                        }
                    }
                }

                // display success notice
                if (!$errors) {
                    set_success('Updated application to version ' . self::$app->version);
                    self::$app->installed_version = self::$app->version;
                    Atomar::set_system('app_installed_version', self::$app->installed_version);
                }
            }
        }
    }

    /**
     * Uninstalls a single extension
     * @param int $id
     * @return bool
     */
    public static function uninstall_extension(int $id) {
        $ext = \R::load('extension', $id);

        // extensions must be disabled before uninstalling them.
        if ($ext->is_enabled) {
            return false;
        }

        // uninstall function
        $fun = $ext->slug . '\uninstall';
        $ext_path = self::$config['ext_dir'] . $ext->slug . '/install.php';
        try {
            include_once($ext_path);
        } catch (\Exception $e) {
            Logger::log_warning('Could not load the un-installation file for extension ' . $ext->slug, $e->getMessage());
            return false;
        }
        if (function_exists($fun)) {
            if (call_user_func($fun)) {
                $ext->installed_version = '';
                if (!store($ext)) {
                    Logger::log_error('Failed to update extension after uninstalling: ' . $ext->slug);
                    return false;
                }
            } else {
                Logger::log_error('Failed to uninstall extension ' . $ext->slug);
                return false;
            }
        }
        return true;
    }

    /**
     * A hook to run uninstall processes after an extension has been disabled.
     * This is rather dangerous so we are not using this method right now. See uninstall_extension()
     */
    public static function hook_uninstall() {
        $extensions = \R::find('extension', 'is_enabled=\'1\'');
        foreach ($extensions as $ext) {
            // uninstall function
            $fun = $ext->slug . '_uninstall';
            $ext_path = self::$config['ext_dir'] . $ext->slug . '/install.php';
            try {
                include_once($ext_path);
            } catch (\Exception $e) {
                Logger::log_warning('Could not load the un-installation file for extension ' . $ext->slug, $e->getMessage());
                continue;
            }
            if (function_exists($fun)) {
                if (call_user_func($fun)) {
                    $ext->installed_version = '';
                    store($ext);
                } else {
                    Logger::log_error('Failed to uninstall extension ' . $ext->slug);
                }
            }
        }
    }

    /**
     * Returns a variable stored in the database or the default value.
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function get_variable(string $key, string $default) {
        $var = \R::findOne('setting', ' name=? ', array($key));
        if ($var) {
            return $var->value;
        } elseif ($default !== null) {
            // create setting with default value
            $var = \R::dispense('setting');
            $var->name = $key;
            $var->value = $default;
            store($var);
            return $default;
        } else {
            return null;
        }
    }

    /**
     * Sets a variable to be stored in the database
     * @param string $key string
     * @param string $value string if null the variable will be deleted
     * @return bool
     */
    public static function set_variable(string $key, string $value) {
        $var = \R::findOne('setting', ' name=? LIMIT 1', array($key));
        if ($value == null) {
            // delete existing setting
            if ($var->id) {
                try {
                    \R::trash($var);
                    return true;
                } catch (\Exception $err) {
                    return false;
                }
            } else {
                return true;
            }
        } else {
            // store new setting
            if (!$var->id) {
                $var = \R::dispense('setting');
            }
            $var->name = $key;
            $var->value = $value;
            return store($var);
        }
    }

    /**
     * Get the value of a stored system setting. If no setting is found, but a default value is provided
     * the variable will be created and the default value returned.
     * @param string $name the name of the variable
     * @param string $default the default value of the variable.
     * @return string the value of the variable or the default value if specified otherwise null.
     */
    public static function get_system(string $name, string $default = null) {
        $s = \R::findOne('system', ' name=?', array($name));
        if ($s) {
            return $s->value;
        } elseif ($default !== null) {
            // create setting with default value
            $s = \R::dispense('system');
            $s->name = $name;
            $s->value = $default;
            store($s);
            return $default;
        } else {
            return null;
        }
    }

    /**
     * Set the value of a stored system setting. Setting the setting to null  or leaving the value field blank will delete the variable.
     * @param string $name the name of the variable to store.
     * @param string $value the value to be stored in the variable. If no value or null is given the variable will be deleted.
     * @return boolean will return true or false if the variable was successfully created or deleted.
     */
    public static function set_system(string $name, string $value = null) {
        $s = \R::findOne('system', ' name=? LIMIT 1', array($name));
        if ($value == null) {
            // delete existing setting
            if ($s->id) {
                try {
                    \R::trash($s);
                    return true;
                } catch (\Exception $err) {
                    return false;
                }
            } else {
                return true; // setting is already deleted
            }
        } else {
            // store new setting
            if (!$s->id) {
                $s = \R::dispense('system');
            }
            $s->name = $name;
            $s->value = $value;
            return store($s);
        }
    }
}
