<?php

namespace atomar\hook;


use atomar\Atomar;

class Menu implements Hook {

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
     */
    public function process($params, $ext_path, $ext_namespace, $ext, $state) {
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                Atomar::$menu[$key] = self::_merge_menu(Atomar::$menu[$key], $value);
            }
        }
        return $state;
    }

    /**
     * A utility method for hook_menu to merge the menu arrays
     * @param array $original menu array that will be extended
     * @param array $new the new menu array what will add/override elements in the original menu array
     * @return array the new menu array
     */
    private static function _merge_menu($original, $new) {
        $merged_menu = array();
        foreach ($new as $key => $value) {
            if (!is_array($original[$key])) {
                $original[$key] = array();
            }
            if (isset($value['menu'])) {
                $value['menu'] = self::_merge_menu($original[$key]['menu'], $value['menu']);
                $merged_menu[$key] = array_merge($original[$key], $value);
            } elseif (is_array($value)) {
                $merged_menu[$key] = array_merge($original[$key], $value);
            }
        }
        if (!is_array($original)) {
            $original = array();
        }
        if (!is_array($merged_menu)) {
            $merged_menu = array();
        }
        return array_merge($original, $merged_menu);
    }

    /**
     * Executed after the hook implementations have finished executing.
     * @param $state mixed The final state of the hook.
     * @return mixed|void
     */
    public function post_process($state) {

    }
}