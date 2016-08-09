<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Lightbox;
use atomar\core\Templator;

class CAdminRolesCreate extends Lightbox {
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
  var value = human_to_machine($(this).val());
  $('#slug').html(value);
  $('#field-slug').val(value);
}).keyup( function () {
  $(this).change();
});
JS;
        Templator::$css_inline[] = <<<CSS
#slug {
      margin: 30px 0 0 10px;
    }
CSS;
        $permissions = \R::getAll('select * from permission');

        // configure lightbox
        $this->width(500);
        $this->header('New Role');

        echo $this->render_view('admin/modal.role.create.html', array(
            'permissions' => $permissions
        ));
    }

    function POST($matches = array()) {
        // require authentication
        if (!Auth::has_authentication('administer_roles')) {
            set_error('You are not authorized to edit roles');
            $this->redirect('/');
        }

        $slug = $_POST['slug'];
        $name = $_POST['name'];

        $existing_role = \R::findOne('role', 'slug=:slug', array(
            ':slug' => $slug
        ));
        if (!$existing_role) {
            $perm_ids = $_POST['permissions'];
            $permissions = \R::batch('permission', $perm_ids);
            $role = \R::dispense('role');
            $role->name = $name;
            $role->sharedPermissionList = $permissions;
            if (store($role)) {
                set_success('New role successfully created!');
            } else {
                set_error($role->errors());
            }

        } else {
            set_error('The role "' . $slug . '" already exists!');
        }
        $this->redirect('/admin/roles/');
    }

    /**
     * This method will be called before GET, POST, and PUT when the lightbox is returned to e.g. when using lightbox.dismiss_url or lightbox.return_url
     */
    function RETURNED() {

    }
}