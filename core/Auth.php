<?php

namespace atomar\core;
use atomar\Atomar;

/**
 * Authentication class to handle user sessions.
 * Requires RedBean to be initialized before use.
 */
class Auth {
    /**
     * This is the user of the corrent session. You can do whatever you want with this object.
     * @var RedBeanPHP/OODBBean or boolean
     */
    public static $user = false;
    /**
     * @var array
     */
    private static $_config = array();
    /**
     * @var boolean
     */
    private static $_session_active = false;
    /**
     * @var int
     */
    private static $_now = 0;
    /**
     * This is the user of the corrent session. This is for internal use only
     * @var RedBeanPHP/OODBBean
     */
    private static $_user = false;

    /**
     * The current site access log
     * @var RedBeanPHP/OODBBean
     */
    private static $_access = null;

    /**
     * The minimum password length required for registration.
     *
     * @var int
     */
    private static $_min_password_length = 6;

    /**
     * This is the callback method that will be executed
     * @var \Closure
     */
    private static $_auth_failure_callback = null;

    /**
     * Initialize the Authentication manager
     * @param array $configuration the configuration array
     */
    public static function setup($configuration) {
        self::$_config = $configuration;
        self::$_now = time();
        // min_password_length if overridable by the config.
        if (isset(self::$_config['min_password_length'])) {
            self::$_min_password_length = self::$_config['min_password_length'];
        }
        self::$_auth_failure_callback = function ($user, $url) {
            // TODO: might it be better to use a hook?
            set_error('You are not authorized to access ' . $url);
            Logger::log_error('Authentication Failure by user: ' . $user->id . ' at: ' . $url);
            if (!Router::is_url_active('/', true)) {
                Router::go('/');
            } else {
                Router::displayServerResponseCode(500);
            }
        };
    }

    /**
     * Allows the default authentication failure callback to be overridden.
     * @param \Closure $function the callback to execute when authentication fails. Parameters are $user and $url.
     */
    public static function set_auth_failure_callback(\Closure $function) {
        self::$_auth_failure_callback = $function;
    }

    /**
     * Begin authentication
     */
    public static function run() {
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_divisor', 100);
        ini_set('session.gc_maxlifetime', 60*60*60*24*365); // 1 year

        self::_start_session();

        // log access
        self::$_access = \R::dispense('access');
        self::$_access->accessed_at = db_date(self::$_now);
        self::$_access->ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        self::$_access->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
        self::$_access->url = $_SERVER['REQUEST_URI'];
        \R::store(self::$_access);
        $_SESSION['access_id'] = self::$_access->id;

        // Check if all session variables are set
        if (isset($_SESSION['user_id'], $_SESSION['email'], $_SESSION['auth'], $_SESSION['last_activity'])) {
            $user_id = $_SESSION['user_id'];
            $auth = $_SESSION['auth'];
            $last_activity = $_SESSION['last_activity'];

            // Check if session expired. "remember_me" sessions never expire.
            if ((isset($_SESSION['remember_me']) && $_SESSION['remember_me']) || self::$_config['session_ttl'] > self::$_now - $last_activity) {
                // Validate session
                $user = \R::load('user', $user_id);
                if ($user->id) {
                    $hash = self::_generate_auth_token($user);
                    if ($hash == $auth) {
                        $_SESSION['last_activity'] = self::$_now;
                        $user->last_activity = self::$_now;
                        $user->last_user_agent = self::$_access->user_agent;
                        $user->last_ip = self::$_access->ip_address;
                        self::$user = $user;
                        self::$_user = $user;
                        self::$_access->user = self::$_user;
                        store(self::$_access);
                        return true;
                    }
                }
            } else {
                self::logout();
            }
        }
        return false;
    }

    /**
     * Returns the access object that represents the current http request.
     * @return RedBeanPHP
     */
    public static function getAccessEntry() {
        return self::$_access;
    }

    /**
     * Start the session
     */
    private static function _start_session() {
        if (!self::$_session_active) {
            // initialize a new session handler to store sessions in the database.
            session_set_save_handler(new SessionDBHandler(), true);

            self::$_session_active = true;
            $session_name = self::$_config['session_name']; // Set a custom session name
            $secure = self::$_config['secure_session'] ? true : false; // Set to true if using https.
            $httponly = true; // This stops javascript being able to access the session id.

            ini_set('session.use_only_cookies', 1); // Forces sessions to only use cookies.
            $cookieParams = session_get_cookie_params(); // Gets current cookies params.
            session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly);
            session_name($session_name); // Sets the session name to the one set above.
            // Start the php session
            if (!session_start()) {
                set_error('Unable to start the session.');
                return false;
            }
        }
        return true;
    }

    /**
     * Generates the 'auth' token that is used to authenticate sessions
     * @param RedBeanPHP /OODBBean $user the use for which the token will be generated
     * @return string
     */
    private static function _generate_auth_token($user) {
        return hash('sha512', $user->pass_hash . self::$_access->user_agent);;
    }

    /**
     * Logs a user out of the system.
     * If no parameter is given the current user will be logged out softly
     * e.g. the session will remain intact, but authentication will be revoked.
     *
     * If a specific user is given as a parameter that user will receive a hard log out
     * e.g. the entire session will be destroyed.
     *
     * @param RedBeanPHP /OODBBean $user the user to log out
     */
    public static function logout($user = null) {
        if ($user == null || $user->id == self::$_user->id) {
            // log out the current user
            unset($_SESSION['auth']);
            unset($_SESSION['user_id']);
            unset($_SESSION['email']);
            unset($_SESSION['last_activity']);
            unset($_SESSION['remember_me']);
        } else {
            // log out the chosen user
            $session = \R::findOne('session', 'user_id=:id', array(
                ':id' => $user->id
            ));
            if ($session) {
                \R::trash($session);
            }
        }
        self::$_user = false;
        self::$user = false;
        self::$_session_active = false;
    }

    /**
     * Returns an array of users that have the given role
     * @param string $role_slug the role to search by
     * @return array the users in this role.
     */
    public static function get_users_by_role(string $role_slug) {
        return \R::findAll('user', 'role_id in (select id from role where slug=?)', array($role_slug));
    }

    /**
     * A utility method that returns a permission bean given it's slug.
     *
     * @param string $permission_slug the slug of the permission
     * @return object the bean model of the permission or false.
     */
    public static function get_permission($permission_slug) {
        $p = \R::findOne('permission', ' slug=? ', array($permission_slug));
        if ($p->id) {
            return $p;
        } else {
            return false;
        }
    }

    /**
     * Set the required authentication level for accessing the page.
     * Access will be revoked if authentication fails. It will either throw an exception or execute a callback
     *
     * @param mixed $options an array of authentication options or false
     * @throws Unauthorized access exception
     */
    public static function authenticate($options = false) {
        if (!self::has_authentication($options)) {
            self::revoke_access();
        }
    }

    /**
     * Check if the current user has one of the authentication levels.
     * This is effectively an OR.
     * @param mixed $options an array of authentication parameters or false
     * @return boolean true of the authentication passes
     */
    public static function has_authentication($options = false) {
        $revoke = true;
        $levels = array();
        if ($options) {
            // super can access everything
            $revoke = !self::is_super();

            if (is_array($options)) {
                // check users
                if (isset($options['users'])) {
                    foreach ($options['users'] as $user) {
                        // authenticated logged in user
                        if ($user->id && self::$_user && self::$_user->id == $user->id) {
                            $revoke = false;
                        }
                    }
                }
                if (isset($options['permissions'])) {
                    $levels = $options['permissions'];
                }
            } else {
                $levels[] = $options;
            }

            // check levels
            if (self::$_user) {
                $permissions = self::$_user->role->sharedPermissionList;
                if ($permissions) {
                    foreach ($permissions as $permission) {
                        if (in_array($permission->slug, $levels)) {
                            $revoke = false;
                        }
                    }
                }
            }


            // add missing permission levels to the database
            if (Atomar::debug()) {
                foreach ($levels as $level) {
                    $p = \R::findOne('permission', 'slug=?', array($level));
                    if (!$p) {
                        $p = \R::dispense('permission');
                        $p->slug = $level;
                        $p->name = machine_to_human($level);
                        \R::store($p);
                    }
                }
            }
        } else {
            // if there are no options then allow access to everyone.
            $revoke = false;
        }
        return !$revoke;
    }

    /**
     * Check if a user is a super user
     * @param mixed $user The user that will be checked or false
     * @return boolean true if the user is the super user.
     */
    public static function is_super($user = false) {
        return self::has_role('super', $user);
    }

    /**
     * Check if a given user has a given role
     * The currently logged in user will be checked if the $user parameter is left null.
     * @param string $role the name of the role
     * @param $user RedBean or null the user to check against.
     * @return boolean true if the user has the role.
     */
    public static function has_role($role, $user = null) {
        if ($user) {
            return $user->role->slug == $role;
        } else if (self::$_user) {
            return self::$_user->role->slug == $role;
        } else {
            return false;
        }
    }

    /**
     * Revoke access to the user.
     * It will either throw an Exception or execute a callback.
     * @throws \Exception Unauthorized access exception
     */
    public static function revoke_access() {
        if (is_callable(self::$_auth_failure_callback)) {
            call_user_func(self::$_auth_failure_callback, array(
                self::$_user,
                $_SERVER['REQUEST_URI']
            ));
        } else {
            throw new \Exception('Unauthorized access on ' . $_SERVER['REQUEST_URI'], 1);
        }
    }

    /**
     * Returns the minimum password length required for registration.
     *
     * @return int
     */
    public static function min_password_length() {
        return self::$_min_password_length;
    }

    /**
     * Create a new account for the user
     * T
     * @param RedBeanPHP /OODBBean $user the user that will be registered
     * @param string $password the human readable password
     * @param RedBeanPHP /OODBBean $role the role assigned to the user
     * @return mixed the user id if registration succeeded otherwise false.
     */
    public static function register($user, $password, $role) {
        if (!$role->id) {
            Logger::log_warning('A::register: invalid role', $role);
            return false;
        }
        if (strlen($password) < self::$_min_password_length) {
            Logger::log_warning('A::register: password it too short');
            return false;
        }
        if (!is_email($user->email)) {
            Logger::log_warning('A:register: a valid email address is required');
            return false;
        }

        $hash = self::_hash($password);
        if ($hash) {
            $user->pass_hash = $hash;
            if (store($user)) {
                // assign role
                $role->ownUserList[] = $user;
                store($role);
                return $user;
            } else {
                Logger::log_warning('A::register: failed to store user ' . $user->email, $user->errors());
                return false;
            }
        } else {
            // something went wrong
            Logger::log_warning('A::register: failed to generate password hash');
            return false;
        }
    }

    /**
     * Create a secure hash
     * @param string $string the string that will be hashed
     * @return mixed the hashed string or false
     */
    public static function _hash($string) {
        $hasher = new PasswordHash(8, false);

        if (strlen($string) > 72) {
            // passwords should never be longer than 72 characters to prevent DoS attacks
            return false;
        }

        $hash = $hasher->HashPassword($string);
        if (strlen($hash) >= 20) {
            return $hash;
        } else {
            return false;
        }
    }

    /**
     * Log in the user
     * If log in is successful you can access the user via A::$user
     *
     * @param string $email the email of the user
     * @param string $password the human readable password of the user account
     * @param bool $remember_me if set to true the user will be logged in indefinitely
     * @return boolean true if login was successful otherwise false.
     */
    public static function login($email, $password, $remember_me = false) {
        $user = \R::findOne('user', ' email=? AND is_enabled=\'1\' AND role_id IS NOT NULL AND role_id<>\'\'', array($email));
        if (!$user || !$user->id) {
            // invalid email
            return false;
        } else {
            // Gard Against Brute Force Attacks
            $valid_date = db_date(self::$_now - self::$_config['login_attempts_time']);
            $access_logs = \R::find('access', 'login_failed=\'1\' AND user_id=:user_id AND accessed_at=:valid_date', array(
                ':user_id' => $user->id,
                ':valid_date' => $valid_date
            ));
            if (count($access_logs) > self::$_config['login_attempts']) {
                Logger::log_warning('account reached maximum login attempts for this period', $user->id);
                return false;
            }

            // Check Password
            $valid = self::_check_hash($password, $user->pass_hash);

            // check if already logged in
            if (self::$_user && self::$_user->id == $user->id) {
                // kick the user out if credentials are incorrect
                if (!$valid) {
                    self::logout();
                    return false;
                } else {
                    return true;
                }
            }

            // update access
            self::$_access->user = $user;

            if (!$valid) {
                self::$_access->login_failed = '1';
                \R::store(self::$_access);
                return false;
            } else {
                // Success
                $_SESSION['user_id'] = $user->id;
                $_SESSION['email'] = $user->email;
                $_SESSION['auth'] = self::_generate_auth_token($user);
                $_SESSION['last_activity'] = self::$_now;
                $_SESSION['remember_me'] = $remember_me == true; // force to be boolean

                self::$_access->login_failed = '0';
                \R::store(self::$_access);

                // Record login
                $user->last_ip = self::$_access->ip_address;
                $user->last_user_agent = self::$_access->user_agent;
                $user->last_login = db_date(self::$_now);
                \R::store($user);
                self::$user = $user;
                self::$_user = $user;
                return true;
            }
        }
    }

    /**
     * Logs in a user without an email and password.
     * This is handy if using a third party service for authentication e.g. social media
     * @param int $user_id the id of the user who will be logged in
     * @param bool $remember_me if set to true the user will be logged in indefinitely
     * @return bool
     */
    public static function login_silent($user_id, $remember_me = false) {
        $user = \R::load('user',$user_id);
        if (!$user || !$user->id) {
            // user does not exist
            return false;
        } else {
            // update access
            self::$_access->user = $user;

            $_SESSION['user_id'] = $user->id;
            $_SESSION['email'] = $user->email;
            $_SESSION['auth'] = self::_generate_auth_token($user);
            $_SESSION['last_activity'] = self::$_now;
            $_SESSION['remember_me'] = $remember_me == true; // force to be boolean

            self::$_access->login_failed = '0';
            \R::store(self::$_access);

            // Record login
            $user->last_ip = self::$_access->ip_address;
            $user->last_user_agent = self::$_access->user_agent;
            $user->last_login = db_date(self::$_now);
            \R::store($user);
            self::$user = $user;
            self::$_user = $user;
            return true;
        }
    }

    /**
     * Check if a string matches a hash.
     * @param string $string the unhashed string
     * @param string $hash the hashed string
     * @return boolean true if the string matches the hash otherwise false
     */
    private static function _check_hash($string, $hash) {
        $hasher = new PasswordHash(8, false);

        if (strlen($string) > 72) {
            // passwords should never be longer than 72 characters to prevent DoS attacks
            return false;
        }

        // just in case the hash isn't found
        if (!isset($hash) || empty($hash) || $hash == '') {
            $hash = '*';
        }
        return $hasher->CheckPassword($string, $hash);
    }

    /**
     * Grants a user access to the site without creating an authenticated session.
     *
     * @param int $user_id the id of the user who will be logged in
     * @return boolean true if login was successful
     */
    public static function authorize($user_id) {
        // check if already logged in
        if (self::$_user && self::$_user->id == $user_id) return true;
        $user = \R::load('user', $user_id);
        if (!$user->id || !$user->is_enabled == '1') return false;

        self::$_access->user = $user;
        \R::store(self::$_access);

        $ip_address = $_SERVER['REMOTE_ADDR']; // Get the IP address of the user.
        $user_browser = $_SERVER['HTTP_USER_AGENT']; // Get the user-agent string of the user.

        self::$_access->login_failed = '0';
        \R::store(self::$_access);

        // Record login
        $last_login = db_date(self::$_now);
        // $ip_address = $_SERVER['REMOTE_ADDR']; // Get the IP address of the user.
        // $user_browser = $_SERVER['HTTP_USER_AGENT'];
        $user->last_ip = $ip_address;
        $user->last_user_agent = $user_browser;
        $user->last_login = $last_login;
        \R::store($user);
        self::$user = $user;
        self::$_user = $user;
        return true;
    }

    /**
     * Change the user password
     * @param RedBeanPHP /OODBBean $user the user whose password will be changed
     * @param string $new_password the new human readable password
     * @return boolean true if the password change was successful
     */
    public static function set_password($user, $new_password) {
        $hash = self::_hash($new_password);
        if ($hash && $user->id) {
            $user->pass_hash = $hash;
            \R::store($user);
            return true;
        } else {
            // something went wrong
            return false;
        }
    }

    /**
     * Check if a valid password reset token exists.
     * @param string $token the password reset token
     * @return int the user id of the validated user or 0
     */
    public static function validate_pw_reset_token($token) {
        $now = db_date(self::$_now);
        $user = \R::findOne('user', 'pass_reset_token=:token AND is_enabled=\'1\' AND pass_reset_expires_at>:time', array(
            ':token' => $token,
            ':time' => $now
        ));
        return $user->id;
    }

    /**
     * Destroy a password reset token
     * @param RedBeanPHP /OODBBean $user the user whose password reset token will be destroyed
     * @return boolean true if successful otherwise false
     */
    public static function destroy_pw_reset_token($user) {
        if ($user && $user->id) {
            $user->pass_reset_token = null;
            $user->pass_reset_expires_at = null;
            \R::store($user);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create a new password reset token.
     * @param RedBeanPHP /OODBBean $user the user for whome a new password reset token will be generated
     * @return boolean true if successful otherwise false
     */
    public static function make_pw_reset_token($user) {
        if ($user->id) {
            // Generate reset token using the old password hash
            $token = self::_hash($user->pass_hash);
            if ($token) {
                $expire_time = self::$_now + self::$_config['password_reset_ttl'];
                $expires = db_date($expire_time);
                $user->pass_reset_token = $token;
                $user->pass_reset_expires_at = $expires;
                \R::store($user);
                return true;
            } else {
                // something went wrong
                return false;
            }
        } else {
            // User does not exist
            return false;
        }
    }
}
