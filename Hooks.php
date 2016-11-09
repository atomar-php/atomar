<?php
namespace atomar;

use atomar\core\Auth;
use atomar\core\HookReceiver;

class Hooks extends HookReceiver
{

    function hookCron()
    {
        // TODO: Implement onCron() method.
    }

    function hookLibraries()
    {
        // TODO: Implement onLibraries() method.
    }

    function hookMenu()
    {
        // TODO: Implement onMenu() method.
    }

    function hookPermission()
    {
        // TODO: Implement onPermission() method.
    }

    function hookPreBoot()
    {
        // TODO: Implement onPreBoot() method.
    }

    function hookPostBoot()
    {
        // TODO: Implement onPostBoot() method.
    }

    function hookTwig()
    {
        // TODO: Implement onTwig() method.
    }

    function hookInstall()
    {
        // TODO: Implement onInstall() method.
    }

    function hookUninstall()
    {
        // TODO: Implement onUninstall() method.
    }

    function hookRoute()
    {
        $urls = array();
        $authenticated_urls = array(
            // TODO: make api /atomar/api
            '/!/(?P<api>[a-zA-Z\_-]+)/?(\?.*)?' => 'atomar\controller\API',

            '/atomar/logout/?(\?.*)?' => 'atomar\controller\Logout',
            '/atomar/user/(?P<id>\d+)/edit/?(\?.*)?' => 'atomar\controller\UserEdit',
            '/atomar/?(\?.*)?' => 'atomar\controller\Admin',
            '/atomar/users/?(\?.*)?' => 'atomar\controller\Users',
            '/atomar/users/create/?(\?.*)?' => 'atomar\controller\UserAdd',

            '/atomar/permissions/?(\?.*)?' => 'atomar\controller\Permissions',

            '/atomar/roles/?(\?.*)?' => 'atomar\controller\Roles',
            '/atomar/roles/create/?(\?.*)?' => 'atomar\controller\RolesAdd',
            '/atomar/roles/(?P<id>\d+)/edit/?(\?.*)?' => 'atomar\controller\RolesEdit',

            '/atomar/settings/?(\?.*)?' => 'atomar\controller\Settings',

            // TODO: change extensions to modules
            '/atomar/extensions/?(\?.*)?' => 'atomar\controller\Extensions'
        );
        $unauthenticated_urls = array(
            '/atomar/login/?(\?.*)?' => 'atomar\controller\Login',
        );
        $public_urls = array(
            // TODO: make api /atomar/api
            '/!/(?P<api>cron)/?(\?.*)?' => 'atomar\controller\API',
            '/(\?.*)?' => 'atomar\controller\Index',
            '/404/?(\?.*)?' => 'atomar\controller\ExceptionHandler'
        );
        $maintenance_urls = array(
            '/(\?.*)?' => 'atomar\controller\Maintenance'
        );

        // enable appropriate urls
        if (Atomar::get_system('maintenance_mode', '0') == '1' && !Auth::has_authentication('administer_site')) {
            // maintenance mode for non-admin users
            if(!Auth::$user) {
                $urls = $unauthenticated_urls;
            }
            $urls = array_merge($urls, $maintenance_urls, $authenticated_urls, $unauthenticated_urls);
        } else {
            if (!Auth::$user) {
                // require login
                $urls = array_merge($public_urls, $unauthenticated_urls);
            } else {
                // authenticated
                $urls = array_merge($public_urls, $authenticated_urls);
            }
        }
        return $urls;
    }

    function hookPage() {

    }
}