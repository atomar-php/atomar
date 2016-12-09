<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;

class Roles extends Controller {
    function GET($matches = array()) {
        Auth::authenticate('administer_roles');

        $roles = \R::find('role', 'slug!=?', array('super'));
        $roles = \R::exportAll($roles);
        foreach ($roles AS &$r) {
            $sql_permissions = <<<SQL
SELECT
  `p`.`id` AS `id`
FROM
  `permission` AS `p`
LEFT JOIN
  `permission_role` AS `pr` ON `pr`.`permission_id`=`p`.`id`
WHERE
  `pr`.`role_id`=:role
GROUP BY
 `p`.`id`
SQL;
            $permissions = \R::getCol($sql_permissions, array(
                ':role' => $r['id']
            ));
            $r['sharedPermission'] = $permissions;
        }
        unset($permissions);
        $sql_permissions = <<<SQL
SELECT
  `p`.`id` AS `key`,
  `p`.`name` AS `value`
FROM
  `permission` AS `p`
ORDER BY
  `p`.`name` ASC
SQL;
        $permissions = \R::getAll($sql_permissions);
        // render page
        echo $this->renderView('@atomar/views/admin/roles.html', array(
            'roles' => $roles,
            'permissions' => $permissions
        ));
    }

    function POST($matches = array()) {
        Auth::authenticate('administer_roles');
        $roles = \R::find('role', 'slug!=?', array('super'));
        foreach ($roles as $role) {
            if (isset($_POST[$role->slug . '_permissions'])) {
                $perm_ids = $_POST[$role->slug . '_permissions'];
                $permissions = \R::batch('permission', $perm_ids);
                $role->sharedPermissionList = $permissions;
            } else {
                $role->sharedPermissionList = array();
            }
        }
        \R::storeAll($roles);
        set_success('Updated role permissions.');
        $this->go('/atomar/roles');
    }
}