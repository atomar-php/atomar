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

    function hookTwig($twig)
    {
        $multi_select = new \Twig_SimpleFunction('multi_select', function ($options, $selected = array(), $key_field = 'key', $value_field = 'value', $show_blank_option_first = '1') {
            $fields = array(
                'key' => $key_field,
                'value' => $value_field
            );

            $result = $show_blank_option_first ? '<option></option>' : '';
            foreach ($options as $option) {
                $is_selected = '';
                if (in_array($option[$fields['key']], $selected)) {
                    $is_selected = 'selected';
                }
                $result .= '<option value="' . $option[$fields['key']] . '" ' . $is_selected . '>' . $option[$fields['value']] . '</option>';
            }
            echo $result;
        });
        $single_select = new \Twig_simpleFunction('single_select', function ($options, $selected = null, $key_field = 'key', $value_field = 'value') {
            $fields = array(
                'key' => $key_field,
                'value' => $value_field
            );
            $last_group = null;
            $result = '<option></option>';
            foreach ($options as $option) {
                $is_selected = '';
                if ($selected != null && $option[$fields['key']] === $selected) {
                    $is_selected = 'selected';
                }
                // generate groups
                if (isset($option['group'])) {
                    if ($last_group !== $option['group']) {
                        // close last group
                        if ($last_group !== null) {
                            $result .= '</optgroup>';
                        }
                        // open new group
                        $result .= '<optgroup label="' . $option['group'] . '">';
                        $last_group = $option['group'];
                    }
                }
                // generate options
                $result .= '<option value="' . $option[$fields['key']] . '" ' . $is_selected . '>' . $option[$fields['value']] . '</option>';
            }
            echo $result;
        });
        $fancy_date = new \Twig_simpleFunction('fancy_date', function ($date, $allow_empty = false) {
            if ($date == '') {
                echo fancy_date(time(), $allow_empty);
            } else {
                echo fancy_date(strtotime($date), $allow_empty);
            }
        });
        $compact_date = new \Twig_simpleFunction('compact_date', function ($date) {
            if ($date == '') {
                echo compact_date();
            } else {
                echo compact_date(strtotime($date));
            }
        });
        $sectotime = new \Twig_simpleFunction('sectotime', function ($time) {
            echo sectotime($time);
        });
        $simple_date = new \Twig_simpleFunction('simple_date', function ($date) {
            if ($date == '') {
                echo simple_date();
            } else {
                echo simple_date(strtotime($date));
            }
        });
        $word_trim = new \Twig_simpleFunction('word_trim', 'word_trim');
        $letter_trim = new \Twig_simpleFunction('letter_trim', 'letter_trim');
        $print_debug = new \Twig_simpleFunction('print_debug', 'print_debug');
        $relative_date = new \Twig_simpleFunction('relative_date', 'relative_date');
        $twig->addFunction(new \Twig_simpleFunction('strip_tags', 'strip_tags'));
        $twig->addFunction($relative_date);
        $twig->addFunction($multi_select);
        $twig->addFunction($single_select);
        $twig->addFunction($fancy_date);
        $twig->addFunction($compact_date);
        $twig->addFunction($sectotime);
        $twig->addFunction($simple_date);
        $twig->addFunction($word_trim);
        $twig->addFunction($letter_trim);
        $twig->addFunction($print_debug);
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