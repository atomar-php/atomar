<?php
/**
 * Created by PhpStorm.
 * User: joel
 * Date: 11/11/16
 * Time: 12:10 AM
 */

namespace atomar\controller;


use atomar\Atomar;
use atomar\core\Controller;
use atomar\hook\Controls;

class ModuleSettings extends Controller
{

    function __construct($return_url)
    {
        parent::__construct($return_url);


    }

    /**
     * Process GET requests
     * @param array $matches the matched patterns from the route
     */
    function GET($matches = array())
    {
        $controller = $this->loadController($matches['module']);
        $controller->GET($matches);
    }

    /**
     * Process POST requests
     * @param array $matches the matched patterns from the route
     */
    function POST($matches = array())
    {
        $controller = $this->loadController($matches['module']);
        $controller->POST($matches);
    }

    /**
     * Returns the controller for the module
     * @param $module_slug
     */
    private function loadController($module_slug) {
        $module = \R::findOne('extension', 'slug=? and is_enabled=1', array($module_slug));
        if(!$module) {
            set_warning($module_slug . ' could not be found');
            $this->go('/atomar/modules');
        }

        if($module_slug == Atomar::application_namespace()) {
            $controls = Atomar::hookModule(new Controls(), Atomar::application_namespace(), Atomar::application_dir(), null, $module->box(), false, false);
        } else {
            $controls = Atomar::hookModule(new Controls(), $module_slug, Atomar::extension_dir() . $module_slug . DIRECTORY_SEPARATOR, null, $module->box(), false, false);
        }
        if(count($controls) && isset($controls[$module_slug])) {
            $class = $controls[$module_slug];
            $instance = new $class();
            if($instance instanceof  Controller) {
                return $instance;
            } else {
                set_error($module->slug . ' does not have a valid controller');
                $this->go('/atomar/modules');
            }
        } else {
            set_warning($module->slug . ' has no controls');
            $this->go('/atomar/modules');
        }
        return null;
    }
}