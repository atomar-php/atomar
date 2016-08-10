<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;

class AdminPermissions extends Controller {
    function GET($matches = array()) {
        Auth::authenticate('administer_permissions');

        $permissions = \R::findAll('permission', ' ORDER BY slug ASC ');

        // render page
        echo $this->renderView('admin/permissions.html', array(
            'permissions' => $permissions
        ));
    }

    function POST($matches = array()) {
        Auth::authenticate('administer_permissions');

        // $sp = $_POST['super_permissions'];
        $ap = $_POST['admin_permissions'];
        $cp = $_POST['client_permissions'];

        // $super_permissions = \R::batch('permission', $sp);
        $admin_permissions = \R::batch('permission', $ap);
        $client_permissions = \R::batch('permission', $cp);

        $roles = \R::find('role');
        foreach ($roles AS &$r) {
            switch ($r->slug) {
                // case 'super':
                //   $r->sharedPermissionList = $super_permissions;
                //   break;
                case 'admin':
                    $r->sharedPermissionList = $admin_permissions;
                    break;
                case 'client':
                    $r->sharedPermissionList = $client_permissions;
                    break;
            }
        }
        \R::storeAll($roles);
        set_success('Updated role permissions.');
        $this->go('/admin/permissions');
    }
}