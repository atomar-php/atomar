<?php

namespace atomar\hook;


class PostBoot implements Hook {

    /**
     * Hooks may receive optional params
     * @param $params mixed
     */
    function __construct($params = null) {

    }

    /**
     * Executed just before the hook implementation is ran
     * @param $extension mixed The extension in which the hook implementation is running.
     * @return bool true if the hook execution can proceed otherwise false
     */
    public function preProcess($extension) {
        return true;
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
        return $state;
    }

    /**
     * Executed after the hook implementations have finished executing.
     * @param $state mixed The final state of the hook.
     * @return mixed|void
     */
    public function postProcess($state) {
        return $state;
    }

    /**
     * Returns an array of parameters that will be passed to the hook receiver
     * @return array
     */
    public function params()
    {
        return null;
    }
}