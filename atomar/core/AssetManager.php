<?php

namespace atomar\core;
use atomar\Atomar;

/**
 * Class AssetManager serves assets from the core without requiring a complete boot.
 * This allows the system to provide assets even when the boot process fails.
 * @package atomar
 */
class AssetManager {

    /**
     * Runs the asset manager
     */
    public static function run() {
        // TODO: atomar assets should be mapped to urls /atomar/[css|fonts|img|js]/[relative asset path]
        // TODO: extension assets should be mapped to urls /ext/[extension_slug]/[relative asset path]
        // TODO: this class needs to support caching
        $file = self::realpath(Router::request_path());
        if($file !== null) {
            $args = array(
                'content_type' => self::getContentType($file),
            );
            if(!Atomar::$config['debug']) {
                $args['expires'] = Atomar::$config['expires_header'];
            }
            stream_file($file, $args);
            exit;
        }
        // NOTE: extensions must use atomar\core\Templator::resolve_ext_asset(); to correctly generate urls for assets.
        // only the core and application are privileged to have auto asset resolution. Otherwise extensions could override
        // core or application resources.
        // EDIT: this will change once we provide auto resolution to extensions.
    }

    /**
     * Returns the absolute path to a resource
     * @param $path
     * @return string
     */
    public static function realpath($path) {
        // route core assets
        if (substr($path, 0, 8) == '/assets/') {
            $file = Atomar::atomar_dir() . $path;
            if (file_exists($file)) {
                return $file;
            }
        } else {
            // route app assets to application dir
            $content_type = self::getContentType($path);
            if($content_type != 'application/octet-stream') {
                $file = Atomar::application_dir() . $path;
                if (file_exists($file)) {
                    return $file;
                } else {
                    // try to serve the file as is if it matches the extensions path
                    $ext_path_len = strlen(Atomar::extension_dir());
                    if(strlen($path) >= $ext_path_len) {
                        $request_root_path = substr($path, 0, $ext_path_len);
                        if($request_root_path === Atomar::extension_dir() && file_exists($path)) {
                            return $path;
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * Returns the content type of the file
     * @param $file
     * @return string
     */
    private static function getContentType($file) {
        $ext = ltrim(strtolower(strrchr($file, '.')), '.');
        switch ($ext) {
            case 'css';
                return 'text/css';
            case 'js';
                return 'application/javascript';
            case 'json':
                return 'application/json';
            case 'html':
                return 'application/html';
            case 'woff':
                return 'application/font-woff';
            case 'ttf':
                return 'application/x-font-ttf';
            case 'png':
                return 'image/png';
            case 'jpg':
            case 'jpeg':
                return 'image/jpg';
            case 'gif':
                return 'image/gif';
            case 'svg':
                return 'image/svg+xml';
            case 'tiff':
                return 'image/tiff';
            default:
                return 'application/octet-stream';
        }
    }
}