<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;
use atomar\Atomar;
use atomar\core\Logger;
use atomar\core\Templator;

class Install extends Controller {
    private static $css = '';

    function __construct() {
        self::$css = <<<CSS
body {
  padding-top: 40px;
  padding-bottom: 40px;
  background-color: #eee;
}
.form-install {
  max-width: 330px;
  padding: 15px;
  margin: 0 auto;
}
.form-install .form-install-heading, .form-install .checkbox {
  margin-bottom: 10px;
}
.form-install .form-control {
  position: relative;
  font-size: 16px;
  height: auto;
  padding: 10px;
  -webkit-box-sizing: border-box;
  -moz-box-sizing: border-box;
  box-sizing: border-box;
}
.form-install #field-username {
  margin-bottom: -1px;
  border-radius: 0;
}
.form-install #field-email {
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  margin-bottom: -1px;
}
.form-install #field-password {
  margin-bottom: 10px;
  border-top-left-radius: 0;
  border-top-right-radius: 0;
}
.form-install .checkbox {
  font-weight: normal;
}
CSS;
    }

    function GET($matches = array()) {
        if (Atomar::$config['debug']) {
            Templator::$css_inline[] = self::$css;
            echo $this->renderView('install.html');
        } else {
            $message = 'Installation could not be performed because the system is not in debug mode.';
            Logger::log_error($message);
            echo $this->renderView('error.html', array(
                'message' => $message
            ));
        }
    }

    function POST($matches = array()) {
        if (!Atomar::$config['debug']) {
            $message = 'Installation could not be performed because the system is not in debug mode.';
            Logger::log_error($message);
            echo $this->renderView('error.html', array(
                'message' => $message
            ));
            exit;
        }

        $user = \R::dispense('user');
        $user->username = $_POST['email'];
        $user->email = $_POST['email'];
        $user->notes = 'This is the super user.';
        $user->is_enabled = '1';
        $password = $_POST['password'];
        $complete_install = $_POST['full'] == 'on' ? 1 : 0;

        if ($complete_install) {
            try {
                $sql_install = <<<SQL
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
  `username` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `notes` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
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
  KEY `username` (`username`),
  KEY `pass_reset_token` (`pass_reset_token`),
  KEY `last_activity` (`last_activity`),
  KEY `is_enabled` (`is_enabled`),
  CONSTRAINT `cons_fk_user_role_id_id` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

SET foreign_key_checks = 1;

-- Insert permissions

INSERT INTO `permission` (`id`, `slug`, `name`) VALUES
  (1, 'administer_site', 'Administer site'),
  (2, 'administer_users', 'Administer users'),
  (3, 'administer_roles', 'Administer roles'),
  (4, 'administer_extensions', 'Administer extensions'),
  (5, 'administer_permissions', 'Administer permissions');

-- Insert roles

INSERT INTO `role` (`id`, `name`, `slug`) VALUES
  (1, 'Super', 'super'),
  (2, 'Administrator', 'administrator'),
  (3, 'Developer', 'developer');
SQL;
                \R::exec($sql_install);

                set_notice('Trashed old tables.');

                // set installed version so we don't run old migrations
                Atomar::set_system('version', '3.0.6');

            } catch (\Exception $e) {
                set_error('We were unable to empty the database.');
                if (Atomar::$config['debug']) {
                    set_error($e->getMessage(), $e);
                }
            }
        }

        $root_role = \R::findOne('role', 'slug="super"');
        if (!$root_role->id) {
            set_error('The super role could not be found. Please perform a complete install.');
            $this->go('/install');
        }

        $user = Auth::register($user, $password, $root_role);
        if ($user) {
            // Login the super user
            if (!Auth::login($user->username, $password)) {
                $this->go('/user/login');
            } else {
                $this->go('/');
            }
        } else {
            set_error('An error was encountered while setting up your account.');
            Templator::$css_inline[] = self::$css;
            echo $this->renderView('install.html');
        }
    }
}