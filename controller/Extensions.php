<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;
use atomar\Atomar;
use atomar\hook\Install;
use atomar\hook\Permission;
use model\Extension;

/**
 * TODO: we need to finish updating this class.
 * We need to handle what happens when a dependency is missing.
 * Class AdminExtensions
 * @package atomar\controller
 */
class Extensions extends Controller {

    function GET($matches = array()) {
        Auth::authenticate('administer_extensions');

        // prepare the application module
        $app = $this->prepareModule(Atomar::loadModule(Atomar::application_dir(), Atomar::application_namespace()));

        // search for extensions
        $ext_path = Atomar::extension_dir();
        $files = scandir($ext_path);
        $extensions = array();
        foreach ($files as $f) {
            if ($f != '.' && $f != '..' && is_dir($ext_path . $f)) {
                // load extension
                $ext = Atomar::loadModule($ext_path . $f, $f);
                $ext = $this->prepareModule($ext);

                $extensions[$ext->slug] = $ext;
            }
        }

        // evaluate dependencies
        $rendered_extensions = array();
        foreach($extensions as $ext) {
            $dependencies = array();
            if(isset($ext->dependencies) && strlen($ext->dependencies)) {
                foreach (explode(',', $ext->dependencies) as $d) {
                    $exists = isset($extensions[$d]);
                    $enabled = $exists ? $extensions[$d]->is_enabled : '0';
                    $dependencies[] = array(
                        'slug' => $d,
                        'exists' => $exists ? '1' : '0',
                        'is_enabled' => $enabled
                    );
                }
            }
            $rendered_extensions[$ext->slug] = $ext->export();
            $rendered_extensions[$ext->slug]['dependencies'] = $dependencies;
        }

        // clean out old extensions
        $valid_names = array_keys($extensions);
        $valid_names[] = Atomar::application_namespace();
        \R::exec('DELETE FROM `extension` WHERE `name` NOT IN (' . \R::genSlots($valid_names) . ') ', $valid_names);

        // render view
        echo $this->renderView('admin/extensions.html', array(
            'modules' => $rendered_extensions,
            'modules_dir' => Atomar::extension_dir(),
            'app' => $app->export()
        ));
    }

    /**
     * Enables a single module if possible
     *
     * @param Extension $module the db record of the module to be enabled
     * @return Extension the module
     */
    private function prepareModule($module) {
        if ($module !== null) {
            $module->is_update_pending = '0';
            if ($module->installed_version && $module->is_enabled) {
                // check for updates
                if (vercmp($module->version, $module->installed_version) == 1) {
                    // TODO: change these to 'has_update'
                    $module->is_update_pending = '1';
                }
            } else {
                $module->is_enabled = '0';
            }

            // check supported core versions
            if (!$module->atomar_version || vercmp($module->atomar_version, Atomar::version()) >= 0) {
                $module->is_supported = '1';
            } else {
                $module->is_supported = '0';
            }
            store($module);
        }
        return $module;
    }

    function POST($matches = array()) {
        $valid_modules = array();
        $modules = array();
        $ids =  array_keys($_POST['extensions']);

        // disable all extensions
        \R::exec('UPDATE extension SET is_enabled=\'0\'');

        // process extensions
        if (isset($ids)) {
            $modules = \R::loadAll('extension', $ids);
        }

        $modules = array_merge($modules, \R::find('extension', 'slug=?', array(Atomar::application_namespace())));

        // validate supported atomar version
        foreach($modules as $m) {
            if ($m->atomar_version && vercmp($m->atomar_version, Atomar::version()) == -1) {
                set_error($m->slug . ' only supports atomar v' . $m->atomar_version);
                continue;
            }
            $valid_modules[$m->slug] = $m;
        }
        $modules = $valid_modules;

        // validate dependencies
        $valid_modules = array();
        foreach($modules as $m) {
            if(isset($m->dependencies) && strlen(trim($m->dependencies))) {
                $dependencies = explode(',', trim($m->dependencies));
                foreach($dependencies as $d) {
                    if(!isset($modules[$d])) {
                        set_error($m->slug . ' is missing dependencies: ' . $m->dependencies);
                        // continue in outer foreach
                        continue 2;
                    }
                }
            }
            $m->is_enabled = '1';
            $valid_modules[$m->slug] = $m;
        }

        \R::storeAll(array_values($valid_modules));

        // install extensions
        Atomar::hook(new Install());

        // rebuild extension permissions
        Atomar::hook(new Permission());

        $this->go();
    }
}