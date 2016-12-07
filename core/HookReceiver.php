<?php
namespace atomar\core;
use atomar\Atomar;
use model\Extension;

/**
 * Interface HookReceiver maps hooks to methods.
 * Any of the hook* methods can be overridden to make use of the hook
 * @package atomar\core
 */
abstract class HookReceiver
{

    /**
     * Reads a set of routes from the /routes directory.
     * Example: /routes/public.json can be loaded by using the 'public' slug.
     *
     * @param Extension $ext the extension who's route will be loaded
     * @param string $slug the name of the routes to be loaded
     */
    protected function loadRoute($ext, $slug) {
        $file = Atomar::atomar_dir() . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . $slug . '.json';
        if($ext != null && $ext->slug == Atomar::application_namespace()) {
            $file = Atomar::application_dir() . 'routes' . DIRECTORY_SEPARATOR . $slug . '.json';
        } else if($ext != null) {
            $file = Atomar::extension_dir() . $ext->slug . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . $slug . '.json';
        }
        $routes = array();
        if(file_exists($file)) {
            $data = file_get_contents($file);
            $data = rtrim($data, "\0");
            $data = str_replace("\n", '', $data);
            $routes = json_decode($data, true);
        }
        if(!is_array($routes)) $routes = array();
        return $routes;
    }

    function hookCron() {

    }

    function hookLibraries() {

    }

    /**
     * @deprecated we will remove the menu hook in the future
     */
    function hookMenu() {

    }

    function hookPermission() {

    }

    function hookPreBoot() {

    }

    function hookPostBoot() {

    }

    /**
     * @param AtomarTwigEnvironment $twig
     */
    function hookTwig($twig) {

    }

    function hookInstall() {

    }

    function hookUninstall() {

    }

    /**
     * @param Extension $ext
     */
    function hookRoute($ext) {

    }

    /**
     * Called before the template is processed.
     * @return array the variables that will be injected into the page
     */
    function hookPage() {
        return array();
    }

    /**
     * Gives the controller to be used for managing the module in the admin page.
     * @return string the class name e.g. 'atomar\controller\Controls'
     */
    function hookControls() {
        return null;
    }

    /**
     * Gives the routes that are available during maintenance mode
     * @param Extension $ext
     * @return array
     */
    function hookMaintenanceRoute($ext) {
        return array();
    }

    /**
     * Gives the catch-all controller for maintenance mode
     * @return Controller|null
     */
    function hookMaintenanceController() {
        return null;
    }
}