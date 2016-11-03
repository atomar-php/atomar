<?php

namespace atomar\core;

/**
 * This is a custom auto loader class that provides easy class loading.
 * Supports classes with namespaces as well.
 */
class AutoLoader {

    private $base_dir;
    private $resolution_index;

    /**
     * AutoLoader constructor.
     *
     * @param string $base_dir base directory (default: __DIR__)
     * @param int $namespace_resolution_index Indicates where along the namespace chain you want to begin resolving. e.g. for an index of 1 "atomar\controller\Admin.php" will resolve to "controller\Admin.php"
     */
    public function __construct($base_dir = null, $namespace_resolution_index) {
        $this->resolution_index = $namespace_resolution_index;
        if ($base_dir === null) {
            $this->base_dir = __DIR__;
        } else {
            $this->base_dir = str_replace(array(
                '\\',
                '/'
            ), DIRECTORY_SEPARATOR, rtrim($base_dir, '/'));
        }
    }

    /**
     * Register a new instance as an SPL AutoLoader.
     *
     * @param $base_dir string A directory to register for class look-ups. (default: __DIR__)
     * @param int $namespace_resolution_index Indicates where along the namespace chain you want to begin resolving. e.g. for an index of 1 "atomar\controller\Admin.php" will resolve to "controller\Admin.php"
     * @return AutoLoader A registered AutoLoader instance
     */
    public static function register($base_dir = null, $namespace_resolution_index = 0) {
        $loader = new self($base_dir, $namespace_resolution_index);
        spl_autoload_register(array(
            $loader,
            'autoload'
        ));

        return $loader;
    }

    /**
     * Autoload classes.
     *
     * @param string $class
     */
    public function autoload($class) {
        // strip off leading namespaces
        for ($i = 0; $i < $this->resolution_index; $i++) {
            if (strpos($class, '\\', 1) !== false) {
                $trimmed = strstr($class, '\\');
                if ($trimmed !== false) {
                    $class = $trimmed;
                }
            }
        }

        // trim leading slash
        if ($class[0] === '\\') {
            $class = substr($class, 1);
        }

        // convert to directory separator
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        $file = sprintf('%s' . DIRECTORY_SEPARATOR . '%s.php', $this->base_dir, str_replace('_', DIRECTORY_SEPARATOR, $class));
        if (is_file($file)) {
            require_once($file);
        }
    }
}
