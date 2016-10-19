<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Lightbox;
use atomar\core\Templator;

class AdminRolesEdit extends Lightbox {
    function GET($matches = array()) {
        // require authentication
        if (!Auth::has_authentication('administer_roles')) {
            set_error('You are not authorized to edit roles');
            $this->redirect('/');
        }

        Templator::$js_onload[] = <<<JS
$('[data-validate]').each(function() {
  var v = new Validate($(this));
});
$('#field-name').change(function() {
  $('#field-slug').html(human_to_machine($(this).val()));
}).keyup( function () {
  $(this).change();
});
JS;
        Templator::$css_inline[] = <<<CSS
#field-slug {
      margin: 30px 0 0 10px;
    }
CSS;

        $role = \R::load('role', $matches['id']);

        if ($role->id) {
            $sql_role_permissions = <<<SQL
SELECT
  `p`.`id` AS `key`
FROM
  `permission` AS `p`
LEFT JOIN
  `permission_role` AS `pr` ON `pr`.`permission_id`=`p`.`id`
WHERE
  `pr`.`role_id`=:role
SQL;
            $role_permissions = \R::getCol($sql_role_permissions, array(
                ':role' => $role->id
            ));
            $role->permissions = $role_permissions;

            $sql_permissions = <<<SQL
SELECT
  `p`.`id` AS `key`,
  `p`.`name` AS `value`
FROM
  `permission` AS `p`
SQL;
            $permissions = \R::getAll($sql_permissions);

            // Configure the lightbox

            $this->width(500);
            $this->header('Edit Role');

            echo $this->renderView('admin/modal.role.edit.html', array(
                'role' => $role,
                'permissions' => $permissions
            ));
        } else {
            $this->redirect('/atomar/roles');
        }
    }

    function POST($matches = array()) {
        // require authentication
        if (!Auth::has_authentication('administer_roles')) {
            set_error('You are not authorized to edit roles');
            $this->redirect('/');
        }

        $role = \R::load('role', $matches['id']);
        if ($role->id) {
            $perm_ids = $_POST['permissions'];
            $permissions = \R::batch('permission', $perm_ids);
            $role->name = $_POST['name'];
            $role->sharedPermissionList = $permissions;
            if (store($role)) {
                set_success('Role successfully updated!');
            } else {
                set_error($role->errors());
            }
        } else {
            set_error('Unknown role');
        }
        $this->redirect('/atomar/roles');
    }

    /**
     * This method will be called before GET, POST, and PUT when the lightbox is returned to e.g. when using lightbox.dismiss_url or lightbox.return_url
     */
    function RETURNED() {

    }
}