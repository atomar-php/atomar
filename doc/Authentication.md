Authentication
----

All the authentication in Atomar is handled by the Auth class (Synonym `A`) located in `includes/auth.php`. The authentication system is implemented using role based permission where a user may have a single role with many permissions.

Authentication starts just early in the boostrap just after the database has been set up and the [Site Configuration](/atomar/documentation/core/Configuration) initialized. During startup the authentication module hijacks the standard PHP Session handling and routes all of the session handling to the database table `session` after which all the standard session variables may be used like nomral but now with a database backend.

###Methods
Below are a list of available methods. These methods are accessible by using the `A` synonym e.g. `A::authenticate('administer_site')`. You can find complete documentation on methods and their parameters by viewing the source code directly.

* `run()` - Starts up the session and performs other authentication initialization.
* `has_authentication($options = false) -> boolean` - validates the current user againts the authentication options. Here are som example $options

        // just a single string. The user must have this permission
        'access_site'

        // an array. The user only needs to validate against one of these to proceed
        array(
            'users'=>array($user1, $user2),
            'permissions'=>array('view_my_page','edit_page_settings') 
        )
* `authenticate($options = false)` - same as above except instead of returning a boolean it will call `revoke_access()`.
* `revoke_access()` -> calls the authentication failure handler or throws an exception.
* `is_super($user=false) -> boolean` - checks if the specified user or the current user is the super user.
* `has_role($role, $user=null) -> boolean` - checks if the specified user or current user has the given role
* `register($user, $password, $role_id) -> boolean` - registers a brand new user account.
* `logout($user=null)` - logs out the specified user or the current user if null.
* `login($username, $password) -> boolean` - logs the corresponding user into the system.
* `set_password($user, $new_password) -> boolean` - sets a new password for the user
* `validate_pw_reset_token($token) -> user_id` - validates the password reset token
* `destroy_pw_reset_token($user) -> boolean` - destroys the password reset token 
* `make_pw_reset_token($user) -> boolean` - creates a new password reset token and stores it in the user object

###Users
See [Users](/atomar/documentation/core/users)

###Permissions
Permissions are a large part of the Authentication module. When `A::authenticate()` or `A::has_authentication()` are used the user permissions are validated against the required permissions and approved or rejected depending on whether or not the user has the appropriate permissions. If the site is in debug mode permissions will be automatically added to the database as they are used in the code. This allows developers to develop with permissions quickly without having to stop to set up new values in the database.