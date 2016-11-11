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
        $sql = <<<SQL
SET foreign_key_checks = 0;

DROP TABLE IF EXISTS `access`;

CREATE TABLE `access` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `accessed_at` datetime DEFAULT NULL,
  `ip_address` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `login_failed` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_access_user` (`user_id`),
  KEY `accessed_at` (`accessed_at`),
  KEY `login_failed` (`login_failed`),
  CONSTRAINT `c_fk_access_user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `activity`;

CREATE TABLE IF NOT EXISTS `activity` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` datetime DEFAULT NULL,
  `message` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `model` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `model_id` int(10) unsigned DEFAULT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_activity_user` (`user_id`),
  CONSTRAINT `cons_fk_activity_user_id_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `extension`;

CREATE TABLE IF NOT EXISTS `extension` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` varchar(600) COLLATE utf8_unicode_ci DEFAULT NULL,
  `version` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `installed_version` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `core` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dependencies` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_supported` enum('0','1') COLLATE utf8_unicode_ci DEFAULT '0',
  `is_enabled` enum('0','1') COLLATE utf8_unicode_ci DEFAULT '0',
  `is_update_pending` enum('0','1') COLLATE utf8_unicode_ci DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`),
  KEY `is_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `log`;

CREATE TABLE IF NOT EXISTS `log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `message` varchar(600) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` text COLLATE utf8_unicode_ci,
  `type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `access_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `access_id` (`access_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `permission`;

CREATE TABLE IF NOT EXISTS `permission` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `permission_role`;

CREATE TABLE IF NOT EXISTS `permission_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `permission_id` int(11) unsigned DEFAULT NULL,
  `role_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQ_95e80a2c1e59e79fb65e2920266bc06199ea20cb` (`permission_id`,`role_id`),
  KEY `index_for_permission_role_permission_id` (`permission_id`),
  KEY `index_for_permission_role_role_id` (`role_id`),
  CONSTRAINT `permission_role_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `permission` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_role_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `role`;

CREATE TABLE IF NOT EXISTS `role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `session`;

CREATE TABLE IF NOT EXISTS `session` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_activity` int(11) unsigned DEFAULT NULL,
  `data` text COLLATE utf8_unicode_ci,
  `user_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `setting`;

CREATE TABLE IF NOT EXISTS `setting` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` varchar(600) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `system`;

CREATE TABLE IF NOT EXISTS `system` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `user`;

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_enabled` enum('0','1') COLLATE utf8_unicode_ci DEFAULT '0',
  `pass_hash` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `role_id` int(11) unsigned DEFAULT NULL,
  `last_ip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_user_agent` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_activity` int(11) unsigned DEFAULT NULL,
  `pass_reset_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pass_reset_expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_foreignkey_user_role` (`role_id`),
  KEY `pass_reset_token` (`pass_reset_token`),
  KEY `last_activity` (`last_activity`),
  KEY `is_enabled` (`is_enabled`),
  CONSTRAINT `cons_fk_user_role_id_id` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

SET foreign_key_checks = 1;

-- Insert permissions

INSERT INTO `permission` (`slug`, `name`) VALUES
  ('administer_site', 'Administer site');

-- Insert roles

INSERT INTO `role` (`name`, `slug`) VALUES
  ('Super', 'super');
SQL;
        \R::begin();
        try {
            \R::exec($sql);
            \R::commit();
            // TODO: I think this is deprecated
            Atomar::set_system('version', Atomar::version());
        } catch (\Exception $e) {
            \R::rollback();
            throw $e;
        }
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

            '/atomar/modules/?' => 'atomar\controller\Extensions',
            '/atomar/modules/(?P<module>[a-z0-9\_]+)/?(\?.*)?' => 'atomar\controller\ModuleSettings'
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