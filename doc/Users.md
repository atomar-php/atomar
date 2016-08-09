Users
----
Atomar provides a lot of built in user management. The currently logged in user may be accessed using the Auth class `A`. For example `A::$user` will return the user object. If the current user is not logged in this property will return `false`.

####Registration
You may easily create a new user by registering them through the Auth system
  
    $password = 'xyz';
    $role_id = 1;
    $user = R::dispense('user');
    $user->username = $_POST['username'];
    $user->email = $_POST['email'];

    // populate extra user data
    $user->first_name = $_POST['first_name'];
    $user->last_name = $_POST['last_name'];
    $user->phone = $_POST['phone'];
    $user->notes = $_POST['notes'];
    $user->is_enabled = $_POST['enabled'] == 'on'?'1':'0';

    // register user
    A::register($user, $password, $role);


### Roles

Permissions are based on role. Each user has a single role to which any number of permissions may be assigned. Roles can be created and edited by visiting [/admin/roles/](/admin/roles/). You can assign roles to users by editing the user at [/admin/users](/admin/users)

### Permissions

As explained above, permission are assigned to roles and each user gets one role. You do not need to create a permission before using it in the code. While Atomar is in debug mode any new permissions will be automatically created and viewable at [/admin/permissions/](/admin/permissions) where you may edit or delete existing permissions. See [Authentication](/admin/documentation/core/Authentication) for more information about permissions.


