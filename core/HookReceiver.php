<?php
namespace atomar\core;

/**
 * Interface HookReceiver maps hooks to methods.
 * Any of the hook* methods can be overridden to make use of the hook
 * @package atomar\core
 */
abstract class HookReceiver
{
    function hookCron() {

    }

    function hookLibraries() {

    }

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

    function hookRoute() {

    }

    function hookPage() {

    }

    /**
     * Gives the controller to be used for managing the module in the admin page.
     * @return string the class name e.g. 'atomar\controller\Controls'
     */
    function hookControls() {
        return null;
    }
}