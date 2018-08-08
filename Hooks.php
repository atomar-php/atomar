<?php
namespace atomar;

use atomar\controller\Maintenance;
use atomar\controller\ServerResponseHandler;
use atomar\core\Auth;
use atomar\core\AutoLoader;
use atomar\core\HookReceiver;
use atomar\core\Router;
use atomar\core\Templator;
use atomar\hook\UserProfileRoute;

class Hooks extends HookReceiver
{

    function hookCron() {}

    function hookLibraries() {}

    function hookMenu()
    {
        $menu = array(
            'primary_menu' => array(),
            'secondary_menu' => array()
        );

        // admin users
        if (Auth::has_authentication('administer_site')) {
            $menu['primary_menu']['/atomar'] = array(
                'link' => l('administer', '/atomar'),
                'class' => array(),
                'weight' => 9999,
                'access' => 'administer_site',
                'menu' => array()
            );
        }
        if (Auth::$user) {
            $menu['primary_menu']['/atomar/logout'] = array(
                'link' => l('logout', '/atomar/logout'),
                'class' => array(),
                'weight' => 0,
                'access' => array(),
                'menu' => array()
            );
        } else {
            $menu['primary_menu']['/atomar/login'] = array(
                'link' => l('login', '/atomar/login'),
                'class' => array(),
                'weight' => 0,
                'access' => array(),
                'menu' => array()
            );
        }

        // secondary menu
        $menu['secondary_menu']['admin_menu'] = array(
            'title' => 'Admin Menu',
            'class' => array('section-header'),
            'weight' => -9999,
            'access' => array(),
            'menu' => array()
        );
        $menu['secondary_menu']['/atomar'] = array(
            'link' => l('Admin home', '/atomar'),
            'options' => array(
                'active' => 'exact'
            ),
            'class' => array(),
            'weight' => -8888,
            'access' => 'administer_site',
            'menu' => array()
        );
        $menu['secondary_menu']['/atomar/users'] = array(
            'link' => l('Users', '/atomar/users/'),
            'class' => array(),
            'weight' => 500,
            'access' => 'administer_site',
            'menu' => array(),
        );
        $menu['secondary_menu']['/atomar/roles'] = array(
            'link' => l('Roles', '/atomar/roles/'),
            'class' => array(),
            'weight' => 600,
            'access' => 'administer_site',
            'menu' => array()
        );
        $menu['secondary_menu']['/atomar/permissions'] = array(
            'link' => l('Permissions', '/atomar/permissions/'),
            'class' => array(),
            'weight' => 700,
            'access' => 'administer_site',
            'menu' => array()
        );
        $menu['secondary_menu']['/atomar/settings'] = array(
            'link' => l('Settings', '/atomar/settings/'),
            'class' => array(),
            'weight' => 800,
            'access' => 'administer_site',
            'menu' => array()
        );
        $menu['secondary_menu']['/atomar/modules'] = array(
            'link' => l('Modules', '/atomar/modules/'),
            'class' => array(),
            'weight' => 900,
            'access' => 'administer_site',
            'menu' => array()
        );
        return $menu;
    }

    function hookPermission() {}

    function hookPreBoot()
    {
        /**
         * PHP Info
         */
        if (Atomar::debug() && isset($_REQUEST['phpinfo']) && $_REQUEST['phpinfo'] && Auth::has_authentication('administer_site')) {
            phpinfo();
            exit;
        }

        /**
         * Autoload extensions
         *
         */
        $extensions = \R::find('extension', 'is_enabled=\'1\'');
        foreach ($extensions as $ext) {
            AutoLoader::register(realpath(Atomar::extension_dir() . $ext->slug));
        }

        // create cache and file directories
        if (!file_exists(Atomar::$config['cache'])) {
            mkdir(Atomar::$config['cache'], 0775, true);
        }
        if (!file_exists(Atomar::$config['files'])) {
            mkdir(Atomar::$config['files'], 0770, true);
        }

        // validate cache and files path
        if (Auth::has_authentication('administer_site')) {
            if (!is_writable(Atomar::$config['cache'])) {
                set_warning('The cache directory (' . Atomar::$config['cache'] . ') is not write-able');
            }
            if (!is_writable(Atomar::$config['files'])) {
                set_warning('The files directory (' . Atomar::$config['files'] . ') is not write-able');
            }
        }
    }

    function hookPostBoot()
    {

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
        $is_url_active = new \Twig_SimpleFunction('is_url_active', function($url, $exact=false) {
            return Router::is_url_active($url, $exact);
        });
        $word_trim = new \Twig_simpleFunction('word_trim', 'word_trim');
        $letter_trim = new \Twig_simpleFunction('letter_trim', 'letter_trim');
        $print_debug = new \Twig_simpleFunction('print_debug', 'print_debug');
        $relative_date = new \Twig_simpleFunction('relative_date', 'relative_date');
        $hook_profile_route = new \Twig_SimpleFunction('hook_profile_route', function($user) {
            return Atomar::hook(new UserProfileRoute($user));
        });
        $twig->addFunction(new \Twig_simpleFunction('strip_tags', 'strip_tags'));
        $twig->addFunction($hook_profile_route);
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
        $twig->addFunction($is_url_active);
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
  `is_supported` int(1) COLLATE utf8_unicode_ci DEFAULT '0',
  `is_enabled` int(1) COLLATE utf8_unicode_ci DEFAULT '0',
  `is_update_pending` int(1) COLLATE utf8_unicode_ci DEFAULT '0',
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

    function hookRoute($ext)
    {
        $admin_urls = $this->loadRoute($ext, 'admin');
        $anonymous_urls = $this->loadRoute($ext, 'anonymous');
        $urls = $this->loadRoute($ext, 'public');

        // enable appropriate urls
        if (!Auth::$user) {
            $urls = array_merge($urls, $anonymous_urls);
        } else if(Auth::has_authentication('administer_site')) {
            $urls = array_merge($urls, $admin_urls);
        }
        return $urls;
    }

    function hookStaticAssets($module)
    {
        return $this->loadRoute($module, 'assets');
    }

    function hookPage() {
        // give the user info to js
        if (Auth::$user) {
            $id = Auth::$user->id;
            $is_admin = Auth::has_authentication('administer_site') ? 'true' : 'false';
            $is_super = Auth::is_super() ? 'true' : 'false';
            Templator::$js_onload[] = <<<JAVASCRIPT
var user = {
  id:$id,
  is_admin:$is_admin,
  is_super:$is_super
}
if(typeof RegisterGlobal == 'function') RegisterGlobal('user', user);
JAVASCRIPT;
        }
        return array();
    }

    function hookMaintenanceController()
    {
        return new Maintenance();
    }

    function hookMaintenanceRoute($ext)
    {
        $urls = $this->loadRoute($ext, 'maintenance');
        return $urls;
    }

    function hookServerResponseCode($code)
    {
        return new ServerResponseHandler();
    }
}