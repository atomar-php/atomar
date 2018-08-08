<?php

namespace atomar\core;
use atomar\Atomar;

/**
 * Authentication class to handle user sessions.
 * Requires RedBean to be initialized before use.
 */
class Auth {
    protected static $session_max_lifetime = 31536000; // 1 year

    /**
     * This is the user of the current session.
     * @var \RedBeanPHP\OODBBean or boolean
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
     * The current site access log
     * @var \RedBeanPHP\OODBBean
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
        // min_password_length is override-able by the config.
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
        self::bootSession();

        self::recordAccess();

        if(self::validateSession()) {
            if(!self::authenticateSession()) {
                // authentication failed
                // TRICKY: keep the session intact to maintain custom state e.g. flash messages
                self::$user = false;
                unset($_SESSION['user_id']);
                unset($_SESSION['email']);
                unset($_SESSION['auth']);
                unset($_SESSION['remember_me']);
            } else {
                // update user activity
                $user = \R::load('user', $_SESSION['user_id']);
                $user->last_activity = time();
                $user->last_user_agent = self::$_access->user_agent;
                $user->last_ip = self::$_access->ip_address;
                self::$user = $user;
                self::$_access->user = self::$user;
                store(self::$_access);

                // periodically regenerate authenticated sessions
                if (rand(1, 100) <= 5) self::regenerateSession();
            }
        } else {
            // session id is obsolete
            self::logout();
        }
        $_SESSION['last_activity'] = time();
        $_SESSION['access_id'] = self::$_access->id;
    }

    /**
     * Returns the access object that represents the current http request.
     * @return \RedBeanPHP\OODBBean
     */
    public static function getAccessEntry() {
        return self::$_access;
    }

    /**
     * Start the session
     */
    private static function bootSession() {
        if (!self::$_session_active) {
            // initialize a new session handler to store sessions in the database.
            session_set_save_handler(new SessionDBHandler(), true);

            self::$_session_active = true;
            $secure = self::$_config['secure_session'] ? true : false; // Set to true if using https.
            $httponly = true; // This stops javascript being able to access the session id.

            ini_set('session.use_only_cookies', 1); // Forces sessions to only use cookies.
            ini_set('session.use_trans_sid', 0);
            ini_set('session.gc_probability', 1);
            ini_set('session.gc_divisor', 100);
            ini_set('session.gc_maxlifetime', self::$session_max_lifetime);

            $cookieParams = session_get_cookie_params(); // Gets current cookies params.
            session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly);
            session_name(self::$_config['session_name']);

            // Start the php session
            session_start();
        }
    }

    /**
     * Generates the 'auth' token that is used to authenticate sessions
     * @param RedBeanPHP /OODBBean $user the use for which the token will be generated
     * @return string
     */
    private static function generateAuthToken($user) {
        return hash('sha512', $user->pass_hash . $user->id . $user->email);
    }

    /**
     * Generates a new session id
     */
    protected static function regenerateSession() {
        // If this session is obsolete it means there already is a new id
        if(isset($_SESSION['OBSOLETE']) || $_SESSION['OBSOLETE'] == true) return;

        // Set current session to expire in 10 seconds
        $_SESSION['OBSOLETE'] = true;
        $_SESSION['EXPIRES'] = time() + 10;

        // Create new session without destroying the old one
        session_regenerate_id(false);

        // Grab current session ID and close both sessions to allow other scripts to use them
        $newSession = session_id();
        session_write_close();

        // Set session ID to the new one, and start it back up again
        session_id($newSession);
        session_start();

        // Now we unset the obsolete and expiration values for the session we want to keep
        unset($_SESSION['OBSOLETE']);
        unset($_SESSION['EXPIRES']);
    }

    /**
     * Checks if the session id is still valid
     * @return bool
     */
    protected static function validateSession() {
        if(isset($_SESSION['OBSOLETE']) && !isset($_SESSION['EXPIRES'])) return false;
        if(isset($_SESSION['EXPIRES']) && $_SESSION['EXPIRES'] < time()) return false;
        return true;
    }

    /**
     * Checks if the session is authenticated
     * @return bool
     */
    protected static function authenticateSession() {
        if(!isset($_SESSION['user_id'], $_SESSION['auth'], $_SESSION['last_activity'])) return false;
        $remember_me = isset($_SESSION['remember_me']) && $_SESSION['remember_me'] == true;
        if(self::$_config['session_ttl'] < time() - $_SESSION['last_activity'] && !$remember_me) return false;
        $user = \R::load('user', $_SESSION['user_id']);
        if (!$user->id) return false;
        $hash = self::generateAuthToken($user);
        return $hash == $_SESSION['auth'];
    }

    /**
     * Logs the access to the db
     */
    protected static function recordAccess() {
        self::$_access = \R::dispense('access');
        self::$_access->accessed_at = db_date(time());
        self::$_access->ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        self::$_access->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
        self::$_access->url = $_SERVER['REQUEST_URI'];
        \R::store(self::$_access);
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
        if ($user == null || $user->id == self::$user->id) {
            // log out current user
            $_SESSION = array();
            self::$user = false;
            self::$_session_active = false;
            session_destroy();
            session_start();
        } else {
            // log out targeted user
            $session = \R::findOne('session', 'user_id=?', array($user->id));
            if ($session) \R::trash($session);
        }
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
     * @return object|boolean the bean model of the permission or false.
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
     * @throws \Exception access exception
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
                        if ($user->id && self::$user && self::$user->id == $user->id) {
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
            if (self::$user) {
                $permissions = self::$user->role->sharedPermissionList;
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
     * @param $user \model\User or null the user to check against.
     * @return boolean true if the user has the role.
     */
    public static function has_role($role, $user = null) {
        if ($user) {
            return $user->role->slug == $role;
        } else if (self::$user) {
            return self::$user->role->slug == $role;
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
                self::$user,
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
     *
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
        self::regenerateSession();
        $user = \R::findOne('user', ' email=? AND is_enabled=1 AND role_id IS NOT NULL AND role_id<>\'\'', array($email));
        if (!$user || !$user->id) {
            // invalid email
            return false;
        } else {
            // Gard Against Brute Force Attacks
            $valid_date = db_date(time() - self::$_config['login_attempts_time']);
            $access_logs = \R::find('access', 'login_failed=1 AND user_id=:user_id AND accessed_at=:valid_date', array(
                ':user_id' => $user->id,
                ':valid_date' => $valid_date
            ));
            if (count($access_logs) > self::$_config['login_attempts']) {
                Logger::log_warning('account reached maximum login attempts for this period', $user->id);
                return false;
            }

            // Check Password
            $valid = self::validatePasswordHash($password, $user->pass_hash);

            // check if already logged in
            if (self::$user && self::$user->id == $user->id) {
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
                self::$_access->login_failed = 1;
                \R::store(self::$_access);
                return false;
            } else {
                // Success
                $_SESSION['user_id'] = $user->id;
                $_SESSION['email'] = $user->email;
                $_SESSION['auth'] = self::generateAuthToken($user);
                $_SESSION['last_activity'] = time();
                $_SESSION['remember_me'] = !!$remember_me;

                self::$_access->login_failed = '0';
                \R::store(self::$_access);

                // Record login
                $user->last_ip = self::$_access->ip_address;
                $user->last_user_agent = self::$_access->user_agent;
                $user->last_login = db_date(time());
                \R::store($user);
                self::$user = $user;
                return true;
            }
        }
    }

    /**
     * Logs in the user without an email and password.
     * This is handy if using a third party service for authentication e.g. social media
     *
     * @param int $user_id the id of the user who will be logged in
     * @param bool $remember_me if set to true the user will be logged in for self::$session_max_lifetime
     * @return bool
     */
    public static function login_silent($user_id, $remember_me = false) {
        self::regenerateSession();
        $user = \R::load('user', $user_id);
        if (!$user || !$user->id) {
            // user does not exist
            return false;
        } else {
            // update access
            self::$_access->user = $user;

            $_SESSION['user_id'] = $user->id;
            $_SESSION['email'] = $user->email;
            $_SESSION['auth'] = self::generateAuthToken($user);
            $_SESSION['last_activity'] = time();
            $_SESSION['remember_me'] = !!$remember_me;

            self::$_access->login_failed = '0';
            \R::store(self::$_access);

            // Record login
            $user->last_ip = self::$_access->ip_address;
            $user->last_user_agent = self::$_access->user_agent;
            $user->last_login = db_date(time());
            \R::store($user);
            self::$user = $user;
            return true;
        }
    }

    /**
     * Check if a string matches a hash.
     * @param string $string the un-hashed string
     * @param string $hash the hashed string
     * @return boolean true if the string matches the hash otherwise false
     */
    private static function validatePasswordHash($string, $hash) {
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
     * TODO: does this still work?
     *
     * @param int $user_id the id of the user who will be logged in
     * @return boolean true if login was successful
     */
    public static function authorize($user_id) {
        // check if already logged in
        if (self::$user && self::$user->id == $user_id) return true;
        $user = \R::load('user', $user_id);
        if (!$user->id || !$user->is_enabled == 1) return false;

        self::$_access->user = $user;
        \R::store(self::$_access);

        self::$_access->login_failed = '0';
        \R::store(self::$_access);

        // Record login
        $last_login = db_date(time());
        $user->last_ip = $_SERVER['REMOTE_ADDR'];
        $user->last_user_agent = $_SERVER['HTTP_USER_AGENT'];
        $user->last_login = $last_login;
        \R::store($user);
        self::$user = $user;
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
        $now = db_date(time());
        $user = \R::findOne('user', 'pass_reset_token=:token AND is_enabled=1 AND pass_reset_expires_at>:time', array(
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
                $expire_time = time() + self::$_config['password_reset_ttl'];
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
