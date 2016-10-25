<?php

namespace atomar\controller;

use atomar\core\ApiController;
use atomar\core\Auth;
use atomar\Atomar;
use atomar\core\Logger;
use atomar\hook\Cron;

/**
 * Class API
 */
class API extends ApiController {

    /**
     * Abstract method
     * Allows you to perform any additional actions before get requests are processed
     * @param array $matches
     */
    protected function setup_get($matches = array()) {

    }

    /**
     * Abstract method
     * Allows you to perform any additional actions before post requests are processed
     * @param array $matches
     */
    protected function setup_post($matches = array()) {

    }

    /**
     * Enables or disables the maintenance mode
     * @param $enabled
     */
    public function get_maintenance($enabled) {
        if (Auth::has_authentication('change_maintenance_mode') || Auth::is_super() || Auth::is_admin()) {
            Atomar::set_system('maintenance_mode', $enabled == true);
        } else {
            set_error('You are not authorized to change the maintenance mode of this site.');
        }
        $this->go_back();
    }

    /**
     * Enables or disables css caching
     * @param $enable
     */
    public function get_cache_css($enable) {
        if (Auth::has_authentication('manage_cache') || Auth::is_super() || Auth::is_admin()) {
            Atomar::set_system('cache_css', $enable == true);
            if ($enable) {
                set_success('CSS caching has been enabled');
            } else {
                set_success('CSS caching has been disabled');
            }
        } else {
            set_error('You are not authorized to change the css caching');
        }
        $this->go_back();
    }

    /**
     * Enables or disables js caching
     * @param $enable
     */
    public function get_cache_js($enable) {
        if (Auth::has_authentication('manage_cache') || Auth::is_super() || Auth::is_admin()) {
            Atomar::set_system('cache_js', $enable == true);
            if ($enable) {
                set_success('JS caching has been enabled');
            } else {
                set_success('JS caching has been disabled');
            }
        } else {
            set_error('You are not authorized to change the js caching');
        }
        $this->go_back();
    }

    /**
     * Clears the asset cache
     */
    public function get_clear_cache() {
        if (Auth::has_authentication('manage_cache') || Auth::is_super() || Auth::is_admin()) {
            if (is_dir(Atomar::$config['cache'])) {
                if (deleteDir(Atomar::$config['cache'])) {
                    set_success('The cache was successfully emptied');
                } else {
                    set_error('The cache could not be emptied');
                    Logger::log_warning('The cached could not be emptied', Atomar::$config['cache']);
                }
            } else {
                set_notice('The cache is empty');
            }
        } else {
            set_error('You are not authorized to clear the cache');
        }
        $this->go_back();
    }

    /**
     * Installs the application
     */
    public function get_install_app() {
        if(Auth::is_super() || Auth::is_admin()) {
            Atomar::install_application();
        } else {
            set_error('You are not authorized to install the application');
        }
        $this->go_back();
    }

    /**
     * Uninstalls an extension
     * @param $id
     */
    public function get_uninstall_extension($id) {
        if (Auth::has_authentication('administer_extensions')) {
            if (Atomar::uninstall_extension($id)) {
                set_success('The extension has been uninstalled');
            } else {
                set_warning('The extension could not be uninstalled');
            }
        } else {
            set_error('You are not authorized to uninstall extensions');
        }
        $this->go_back();
    }

    /**
     * Performs input validation
     * @param $value
     * @param $action
     */
    public function get_validate($value, $action) {
        $result = array();

        // validate values
        switch ($action) {
            case 'project-stage-name':
                Auth::authenticate('administer_project_stages');
                $stage = \R::findOne('projectstage', 'name=:name AND deleted<>\'1\' LIMIT 1', array(
                    ':name' => $value
                ));
                $result = array();
                if ($stage->id) {
                    $result['status'] = 0;
                    $result['msg'] = 'That stage already exists';
                } else {
                    $result['status'] = 1;
                    $result['msg'] = 'OK';
                }
                break;
            case 'extension-name':
                $extension = \R::findOne('extension', 'name=:name LIMIT 1', array(
                    ':name' => $value
                ));
                $result = array();
                if ($extension->id) {
                    $result['status'] = 0;
                    $result['msg'] = 'An extension by that name already exists';
                } else {
                    $result['status'] = 1;
                    $result['msg'] = 'OK';
                }
                break;
            case 'email':
                $result = array();
                if (is_email($value)) {
                    $user = \R::findOne('user', ' email=:email', array(
                        ':email' => $value
                    ));
                    if ($user->id) {
                        $result['status'] = 0;
                        $result['msg'] = 'This email is not available';
                    } else {
                        $result['status'] = 1;
                        $result['msg'] = 'This email is available';
                    }
                } else {
                    $result['status'] = -1;
                    $result['msg'] = 'Invalid email address';
                }
                break;
            case 'username-email':
                // check if the username or email exists
                $result = array();
                if (is_email($value)) {
                    $user = \R::findOne('user', ' email=:email', array(
                        ':email' => $value
                    ));
                    if ($user->id) {
                        $result['status'] = 1;
                        $result['msg'] = 'Ready to reset password';
                    } else {
                        $result['status'] = 0;
                        $result['msg'] = 'We could not find that email';
                    }
                } else {
                    $user = \R::findOne('user', ' username=:username', array(
                        ':username' => $value
                    ));
                    if ($user->id) {
                        $result['status'] = 1;
                        $result['msg'] = 'Ready to reset password';
                    } else {
                        $result['status'] = 0;
                        $result['msg'] = 'We could not find that username';
                    }
                }
                break;
            case 'role-name':
                $role = \R::findOne('role', ' name=:name', array(
                    ':name' => $value
                ));
                if ($role->id) {
                    $result['status'] = 0;
                    $result['msg'] = 'That role already exists';
                } else {
                    $result['status'] = 1;
                    $result['msg'] = 'OK';
                }
                break;
            case 'username':
                $result = array();
                $user = \R::findOne('user', ' username=:username', array(
                    ':username' => $value
                ));
                if ($user->id) {
                    $result['status'] = 0;
                    $result['msg'] = 'Not available';
                } else {
                    $result['status'] = 1;
                    $result['msg'] = 'Available';
                }
                break;
            case 'migration':
                $migration_dir = Atomar::atomar_dir() . '/migration/';
                $from = $_REQUEST['from'];
                $to = $_REQUEST['to'];

                $migration_from = $from . '_*.php';
                $migration_from_files = glob($migration_dir . $migration_from, GLOB_MARK | GLOB_NOSORT);

                $migration_to = '*_' . $to . '.php';
                $migration_to_files = glob($migration_dir . $migration_to, GLOB_MARK | GLOB_NOSORT);
                if (file_exists($migration_dir . $from . '_' . $to . '.php')) {
                    $result = array(
                        'from' => array(
                            'status' => 0,
                            'msg' => 'migration already exists'
                        ),
                        'to' => array(
                            'status' => 0,
                            'msg' => 'migration already exists'
                        )
                    );
                } elseif (count($migration_from_files) > 0) {
                    $result = array(
                        'from' => array(
                            'status' => 0,
                            'msg' => 'migration already exists'
                        ),
                        'to' => array(
                            'status' => -2,
                            'msg' => 'multiple migration paths'
                        )
                    );
                } elseif (count($migration_to_files) > 0) {
                    $result = array(
                        'from' => array(
                            'status' => -2,
                            'msg' => 'multiple migration paths'
                        ),
                        'to' => array(
                            'status' => 0,
                            'msg' => 'migration already exists'
                        )
                    );
                } else {
                    $result = array(
                        'from' => array(
                            'status' => 1,
                            'msg' => 'OK'
                        ),
                        'to' => array(
                            'status' => 1,
                            'msg' => 'OK'
                        )
                    );
                }
                break;
            default:
                $result = array(
                    'status' => 0,
                    'msg' => 'Unknown validation'
                );
                break;
        }
        render_json($result);
    }

    /**
     * Executes cron
     * @param $token
     */
    public function get_cron($token) {
        if (strcmp(trim(Atomar::$config['cron_token']), trim($token)) == 0) {
            echo '== Cron Job started at ' . fancy_date() . '==<br>';
            Atomar::hook(new Cron());
            echo '== Cron Job completed at ' . fancy_date() . '==<br>';
        } else {
            Logger::log_error('Illegal cron attempt', array());
        }
        exit(1);
    }

    /**
     * Enables or disables a user
     * @param $id
     * @param $enabled
     */
    public function get_enable_user($id, $enabled) {
        if (Auth::has_authentication('administer_users')) {
            $user = \R::findOne('user', ' id=:id', array(
                ':id' => $id
            ));
            if ($user->id) {
                if (Auth::is_super($user)) {
                    set_error('The super user cannot be disabled!');
                    $this->go_back();
                }
                $user->is_enabled = $enabled ? '1' : '0';
                \R::store($user);
                if ($user->is_enabled != '1') {
                    Auth::logout($user);
                }
                set_success('User "' . $user->username . '" has been ' . ($user->is_enabled == '1' ? 'en' : 'dis') . 'abled');
            } else {
                set_error('Unknown user');
            }
        } else {
            set_error('You are not authorized to enable users');
        }
        $this->go_back();
    }

    /**
     * Deletes a user
     * @param $id
     */
    public function get_delete_user($id) {
        if (Auth::has_authentication('administer_users')) {
            $user = \R::findOne('user', ' id=:id', array(
                ':id' => $id
            ));
            if ($user->id) {
                if (Auth::is_super($user)) {
                    set_error('The super user cannot be deleted!');
                    $this->go_back();
                }
                Auth::logout($user);
                \R::trash($user);
                set_success('User "' . $user->username . '" has been deleted');
            } else {
                set_error('Unknown user');
            }
        } else {
            set_error('You are not authorized to delete users');
        }
        $this->go_back();
    }

    /**
     * Deletes a role
     * @param $id
     */
    public function get_delete_role($id) {
        if (Auth::has_authentication('administer_roles')) {
            $role = \R::load('role', $id);
            if ($role->id) {
                if ($role->slug == 'super') {
                    set_error('The super user role cannot deleted!');
                } else {
                    $users = $role->ownUser;
                    if (count($users) > 0) {
                        set_notice(count($users) . ' users were removed from the role "' . $role->slug . '"');
                    }
                    \R::trash($role);
                    set_success('Role "' . $role->slug . '" has been deleted');
                }
            } else {
                set_error('Unknown role');
            }
        } else {
            set_error('You are not authorized to delete roles');
        }
        $this->go_back();
    }

    /**
     * Deletes a permission
     * @param $id
     */
    public function get_delete_permission($id) {
        if (Auth::has_authentication('administer_permissions')) {
            $permission = \R::load('permission', $id);
            if ($permission->id) {
                $roles = $permission->sharedRoleList;
                if (count($roles) > 0) {
                    set_notice(count($roles) . ' roles lost the permission "' . $permission->slug . '"');
                }
                \R::trash($permission);
                set_success('Permission "' . $permission->slug . '" has been deleted');
            } else {
                set_error('Unknown permission');
            }
        } else {
            set_error('You are not authorized to delete permissions');
        }
        $this->go_back();
    }

    /**
     * Enables or disables debug mode
     * @param $enable
     */
    public function get_debug_mode($enable) {
        if (Auth::has_authentication('administer_site')) {
            Atomar::set_system('debug', $enable ? '1' : '0');
            set_success('The site has ' . ($enable == '1' ? 'entered' : 'left') . ' debug mode.');
        } else {
            set_error('you are not authorized to change debug mode.');
        }
        $this->go_back();
    }

    /**
     * Saves the inline edit form
     *
     * @param $data
     * @param $model
     */
    public function post_inline_edit($data, $model) {
        $response = array(
            'status' => 'error',
            'message' => 'unknown model'
        );
        if (Auth::has_authentication('edit_' . $model)) {
            $bean = \R::load($model, $data['id']);
            if ($bean->id) {
                $bean[$data['key']] = $data['value'];
                if (store($bean)) {
                    $response['status'] = 'ok';
                    $response['message'] = 'the ' . $model . ' was saved.';
                } else {
                    $response['message'] = 'the ' . $model . ' could not be saved.';
                }
            } else {
                $response['message'] = 'the ' . $model . ' could not be found.';
            }
        } else {
            $response['message'] = 'you are not authorized to edit ' . $model;
        }
        render_json($response);
    }
}