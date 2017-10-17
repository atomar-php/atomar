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
     * @param Extension $module the module who's route will be loaded
     * @param string $slug the name of the routes to be loaded
     */
    protected function loadRoute($module, $slug) {
        $file = Atomar::atomar_dir() . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . $slug . '.json';
        if($module != null && $module->slug == Atomar::application_namespace()) {
            $file = Atomar::application_dir() . 'routes' . DIRECTORY_SEPARATOR . $slug . '.json';
        } else if($module != null) {
            $file = Atomar::extension_dir() . $module->slug . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . $slug . '.json';
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

    /**
     * Gives the controller to display the server status
     * @param int $code the server status code that will be displayed
     * @return Controller|null
     */
    function hookServerResponseCode($code) {
        return null;
    }

    /**
     * Gives url mappings to static directories
     * @param $module
     * @return array
     */
    function hookStaticAssets($module) {
        return array();
    }

    /**
     * Gives the route used for user profiles
     * @param $user
     * @return null
     */
    function hookUserProfileRoute($user) {
        return null;
    }
}