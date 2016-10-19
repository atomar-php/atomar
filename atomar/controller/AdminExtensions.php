<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;
use atomar\Atomar;
use atomar\hook\Permission;

/**
 * TODO: we need to finish updating this class.
 * We need to handle what happens when a dependency is missing.
 * Class AdminExtensions
 * @package atomar\controller
 */
class AdminExtensions extends Controller {

    function GET($matches = array()) {
        Auth::authenticate('administer_extensions');

        // search for extensions
        $ext_path = Atomar::extension_dir();
        $files = scandir($ext_path);
        $extensions = array();
        $rendered_extensions = array();
        $extension_names = array();
        foreach ($files as $f) {
            if ($f != '.' && $f != '..' && is_dir($ext_path . $f)) {
                // load extension
                $ext = Atomar::loadModule($ext_path . $f, $f);

                if ($ext !== null) {
                    // load information
                    $extensions[$f]['slug'] = $f;
                    $extensions[$f]['name'] = $ext->name;
                    $extensions[$f]['description'] = $ext->description;
                    $extensions[$f]['version'] = $ext->version;
                    $extensions[$f]['core'] = $ext->core;
                    $extensions[$f]['dependencies'] = $ext->dependencies;

                    $extension_names[] = $ext->name;

                    // check dependencies
                    $dependencies = array();
                    foreach($ext->sharedExtensionList as $key => $dependency) {
                        $dependencies[] = array(
                            'slug' => $dependency->slug,
                            'exists' => '1',
                            'is_enabled' => $dependency->is_enabled
                        );
                    }

                    if (!$ext->installed_version && $ext->is_enabled) {
                        // this is our first time so record the installed version
                        $ext->installed_version = $extensions[$f]['version'];
                    } else {
                        // check for updates
                        if (vercmp($extensions[$f]['version'], $ext->installed_version) == 1) {
                            $extensions[$f]['is_update_pending'] = '1';
                        } else {
                            $extensions[$f]['is_update_pending'] = '0';
                        }
                    }

                    // check supported core versions
                    if (vercmp($extensions[$f]['core'], Atomar::version()) >= 0) {
                        $extensions[$f]['is_supported'] = '1';
                    } else {
                        $extensions[$f]['is_supported'] = '0';
                    }
                    // update stored extension information
                    if (is_array($extensions[$f]['dependencies'])) {
                        $extensions[$f]['dependencies'] = implode(',', $extensions[$f]['dependencies']);
                    } else {
                        $extensions[$f]['dependencies'] = '';
                    }
                    $ext->import($extensions[$f]);
                    // save new extensions. The rest can wait to increase performance.
                    if (!$ext->id) {
                        \R::store($ext);
                    }
                    $extensions[$f] = $ext;

                    // prepare for rendering on page
                    $rendered_extensions[$f] = $extensions[$f]->with('ORDER BY name')->export();
                    $rendered_extensions[$f]['dependencies'] = $dependencies;
                }
            }
        }
        \R::storeAll($extensions);
        // clean out old extensions
        if (count($extension_names) > 0) {
            \R::exec('DELETE FROM `extension` WHERE `name` NOT IN (' . \R::genSlots($extension_names) . ') ', $extension_names);
        } else {
            \R::wipe('extension');
        }
        // render view
        echo $this->renderView('admin/extensions.html', array(
            'extensions' => $rendered_extensions,
            'ext_dir' => Atomar::extension_dir(),
            'app' => Atomar::getAppInfo()
        ));
    }

    function POST($matches = array()) {
        $extensions = $_POST['extensions'];
        $is_missing_dependencies = false;
        $not_supported = false;
//      $updated_exts = array();
//      $ids = array();
        // disable all extensions
        \R::exec('UPDATE extension SET is_enabled=\'0\'');
        // process extensions
        if (isset($extensions)) {
            foreach ($extensions as $id => $state) {
                $ext = \R::load('extension', $id);

                // check if supported
                if (vercmp($ext->core, Atomar::version()) == -1) {
                    $not_supported = true;
                }

                if (!$this->enable_extension($ext->id)) {
                    $is_missing_dependencies = true;
                }
            }
        }
        // rebuild extension permissions
        Atomar::hook(new Permission());
        // perform custom extension installation.
        Atomar::install_extensions();

        if ($is_missing_dependencies) {
            set_error('Some extensions could not be enabled because they are missing dependencies.');
        }
        if ($not_supported) {
            set_error('Some extensions could not be enabled because they are not supported.');
        }
        $this->go('/atomar/extensions');
    }

    // stores an extension in the db and saves it in the cache

    private function enable_extension($id) {
        $extension = \R::load('extension', $id); //\R::load('extension', $id);

        // validate dependencies
        $dependencies = explode(',', trim($extension->dependencies));
        $missing_dependencies = array_flip($dependencies);
        $required_extensions = \R::find('extension', 'slug IN (' . \R::genSlots($dependencies) . ') ', $dependencies);

        if ($required_extensions) {
            // check if missing
            foreach ($required_extensions as $required_ext) {
                unset($missing_dependencies[$required_ext->slug]);
            }
            if (count($missing_dependencies) > 0) {
                set_notice('missing dependencies');
                return false;
            }

            // enable dependencies
            foreach ($required_extensions as $ext) {
                if (!$this->enable_extension($ext->id)) {
                    // disable the extension
                    $extension->is_enabled = '0';
                    \R::store($extension);
                    return false;
                }
            }
        }

        // Enable extension.
        if (!$extension->installed_version) {
            // NOTE: The installed version begins at 0 so the hook_install can correctly execute all of the updates.
            $extension->installed_version = '0';
        }
        $extension->is_enabled = '1';
        return \R::store($extension);
    }
}