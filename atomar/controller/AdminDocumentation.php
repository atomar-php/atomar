<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;
use atomar\Atomar;
use atomar\core\Logger;
use Michelf\Markdown;

require_once(Atomar::atomar_dir() . '/vendor/Markdown/Michelf/Markdown.inc.php');


class AdminDocumentation extends Controller {
    function GET($matches = array()) {
        Auth::authenticate('view_system_documentation');
        $this->generate_menu();
        $missing = <<<MD
Missing Documentation
----
Unfortunately the documentation for this file could not be found.
MD;
        $markdown = $missing;
        if (isset($matches['type']) && isset($matches['name'])) {
            if ($matches['type'] == 'extension') {
                // get extension documentation
                $markdown = file_get_contents(Atomar::extension_dir() . $matches['name'] . '/README.md');
                if ($markdown === false) {
                    $markdown = $missing;
                }
            } elseif ($matches['type'] == 'core') {
                // get core documentation
                $markdown = file_get_contents(Atomar::atomar_dir() . '/doc/' . $matches['name'] . '.md');
                if ($markdown === false) {
                    $markdown = $missing;
                }
            }
        } else {
            // get system documentation
            $markdown = file_get_contents(Atomar::atomar_dir() . '/README.md');
            if ($markdown === false) {
                $markdown = $missing;
                Logger::log_warning('Could not read the system documentation file');
            }
        }
        $html = Markdown::defaultTransform($markdown);
        echo $this->render_view('admin/documentation.html', array(
            'documentation' => $html
        ));
    }

    /**
     * Process POST requests
     * @param array $matches the matched patterns from the route
     */
    function POST($matches = array()) {
        $this->GET($matches);
    }

    /**
     * Generates the documentation menu
     */
    private function generate_menu() {
        // add core docs
        Atomar::$menu['secondary_menu']['/admin/documentation']['menu']['/core'] = array(
            'title' => 'Atomar',
            'class' => array(),
            'weight' => 0,
            'access' => 'view_system_documentation',
            'menu' => array()
        );
        $docs = glob(Atomar::atomar_dir() . '/doc/*.md');
        $docs = array_flip($docs);
        ksort($docs);
        $weight = 0;
        foreach ($docs as $doc => $index) {
            Atomar::$menu['secondary_menu']['/admin/documentation']['menu']['/core']['menu'][basename($doc)] = array(
                'link' => l(basename($doc, '.md'), '/admin/documentation/core/' . basename($doc, '.md')),
                'class' => array(),
                'weight' => $weight,
                'access' => 'view_system_documentation',
                'menu' => array()
            );
            $weight++;
        }

        // add extension docs
        Atomar::$menu['secondary_menu']['/admin/documentation']['menu']['/ext'] = array(
            'title' => 'Extensions',
            'class' => array(),
            'weight' => 0,
            'access' => 'view_system_documentation',
            'menu' => array()
        );
        $extensions = \R::findAll('extension', ' ORDER BY `name` ASC ');
        $weight = 0;
        foreach ($extensions as $ext) {
            // Call hook_libraries()
            $class = $ext->is_enabled == '1' ? '' : 'text-muted';
            // add documentation link
            Atomar::$menu['secondary_menu']['/admin/documentation']['menu']['/ext']['menu'][$ext->slug] = array(
                'link' => l($ext->name, '/admin/documentation/extension/' . $ext->slug, array($class)),
                'class' => array(),
                'weight' => $weight,
                'access' => 'view_system_documentation',
                'menu' => array()
            );
            $weight++;
        }
    }
}