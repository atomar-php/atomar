<?php

namespace atomar\hook;

// TODO: let this be done in the page hook
class Twig implements Hook {
    private $twig;

    /**
     * Hooks may receive optional params
     * @param $params mixed
     */
    function __construct($params = null) {
        $this->twig = $params;
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
        if ($state == null) {
            $state = array();
        }
        if (is_array($params)) {
            $state = array_merge($state, $params);
        }
        return $state;
    }

    /**
     * Executed after the hook implementations have finished executing.
     * @param $state mixed The final state of the hook.
     * @return mixed|void
     */
    public function postProcess($state) {
        foreach ($state as $key => $value) {
            $twig_fun = new \Twig_simpleFunction($key, $value, array('is_safe' => array('html')));
            $this->twig->addFunction($twig_fun);
        }
        return $state;
    }

    /**
     * Returns an array of parameters that will be passed to the hook receiver
     * @return array
     */
    public function params()
    {
        return $this->twig;
    }
}