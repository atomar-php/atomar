<?php

namespace atomar\hook;


use atomar\Atomar;
use atomar\exception\UnknownController;

class Url implements Hook {

    private $root_ext_dir;

    /**
     * Hooks may receive optional params
     * @param $params mixed
     */
    function __construct($params = null) {

    }

    /**
     * Executed just before the hook implementation is ran
     * @param $function_name string The name of the method that will be ran.
     * @param $extension mixed The extension in which the hook implementation is running.
     */
    public function pre_process($function_name, $extension) {

    }

    /**
     * Executes the hook with the result of the hooked.
     * @param $params mixed The params returned (if any) from the hook implementation.
     * @param $ext_path string The path to the extension's directory
     * @param $ext_namespace string The namespace of the extension
     * @param $ext mixed The extension in which the hook implementation is running.
     * @param $state mixed The last returned state of the hook. If you want to maintain state you should modify and return this.
     * @return mixed The hook state.
     * @throws UnknownController
     */
    public function process($params, $ext_path, $ext_namespace, $ext, $state) {
        if ($state == null) {
            $state = array();
        }
        if (is_array($params)) {
            foreach ($params as $url_regx => $class) {
                $class_path = $class;

                // trim leading slash
                if ($class_path[0] === '\\') {
                    $class_path = substr($class_path, 1);
                }

                // identify application
                $top_namespace = strstr($class_path, '\\', true);
                if($top_namespace !== false && $top_namespace === Atomar::application_namespace()) {
                    // use application path
                    $trimmed = strstr($class_path, '\\');
                    $class_path = Atomar::application_dir() . ltrim($trimmed, '\\');
                }  else {
                    // use root extension dir
                    $class_path = Atomar::extension_dir() . ltrim($class_path, '\\');
                }

                // convert to directory separator
                $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_path) . '.php';

                if (file_exists($class_path)) {
                    include_once($class_path);
                    $state[$url_regx] = ltrim($class, '\\');
                } else {
                    throw new UnknownController('The controller "' . $class . '" could not be found at ' . $class_path);
                }
            }
        }
        return $state;
    }

    /**
     * Executed after the hook implementations have finished executing.
     * @param $state mixed The final state of the hook.
     * @return mixed|void
     */
    public function post_process($state) {
        if ($state == null) {
            $state = array();
        }
        return $state;
    }
}