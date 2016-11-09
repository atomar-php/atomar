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

    function hookTwig() {

    }

    function hookInstall() {

    }

    function hookUninstall() {

    }

    function hookRoute() {

    }

    function hookPage() {

    }
}