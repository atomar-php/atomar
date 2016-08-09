<?php

namespace atomar\core;
use atomar\Atomar;

/**
 * This class handles all of the file caching
 */
class Cache {
    /**
     * Combines and compresses all of the css files into a single cached file.
     * @param int $ttl The length of the time cache should live before being rebuilt. if 0 the cache will never expire
     */
    public static function cache_css($ttl = 0) {
        $hash = md5(Atomar::version());
        $cache_dir = Atomar::$config['cache'] . 'performance/';
        $css_dir = $cache_dir . 'css/';
        $css_cache = $css_dir . $hash . '.css';
        if (file_exists($css_cache)) {
            $cache_life = filemtime($css_cache);
        } else {
            $cache_life = false;
        }

        if (!file_exists($css_dir)) {
            $old = umask(0002);
            if (!mkdir($css_dir, 0775, true)) {
                Logger::log_warning('could not create the cached css directory', $css_dir);
            }
            umask($old);
        }

        $img_link = realpath($cache_dir) . DIRECTORY_SEPARATOR . 'img';
        $fonts_link = realpath($cache_dir) . DIRECTORY_SEPARATOR . 'fonts';
        // link the other assets like fonts and images
        if (!is_dir($img_link)) safe_symlink(realpath(Atomar::atomic_dir() . '/assets/img'), $img_link);
        if (!is_dir($fonts_link)) safe_symlink(realpath(Atomar::atomic_dir() . '/assets/fonts'), $fonts_link);

        // rebuild the cache
        if ($cache_life === false || (time() - $cache_life > $ttl && $ttl > 0)) {
            // combine files
            $css = '';
            foreach (Templator::$css as $file) {
                $resolved_file = AssetManager::realpath($file);
                if (is_file($resolved_file)) {
                    $css .= ' ' . file_get_contents($resolved_file);
                }
            }

            // compress css
            $css = self::minify_css($css);

            // write cache
            if (file_put_contents($css_cache, $css)) {
                chmod($css_cache, 0775);
                Templator::$css = array('/' . ltrim($css_cache, '/'));
            } else {
                Logger::log_error('Failed to cache the css files');
            }
        } else {
            Templator::$css = array('/' . ltrim($css_cache, '/'));
        }
    }

    public static function minify_css($str) {
        # remove comments first (simplifies the other regex)
        $re1 = <<<'EOS'
(?sx)
  # quotes
  (
    "(?:[^"\\]++|\\.)*+"
  | '(?:[^'\\]++|\\.)*+'
  )
|
  # comments
  /\* (?> .*? \*/ )
EOS;

        $re2 = <<<'EOS'
(?six)
  # quotes
  (
    "(?:[^"\\]++|\\.)*+"
  | '(?:[^'\\]++|\\.)*+'
  )
|
  # ; before } (and the spaces after it while we're here)
  \s*+ ; \s*+ ( } ) \s*+
|
  # all spaces around meta chars/operators
  \s*+ ( [*$~^|]?+= | [{};,>~+-] | !important\b ) \s*+
|
  # spaces right of ( [ :
  ( [[(:] ) \s++
|
  # spaces left of ) ]
  \s++ ( [])] )
|
  # spaces left (and right) of :
  \s++ ( : ) \s*+
  # but not in selectors: not followed by a {
  (?!
    (?>
      [^{}"']++
    | "(?:[^"\\]++|\\.)*+"
    | '(?:[^'\\]++|\\.)*+'
    )*+
    {
  )
|
  # spaces at beginning/end of string
  ^ \s++ | \s++ \z
|
  # double spaces to single
  (\s)\s+
EOS;

        $str = preg_replace("%$re1%", '$1', $str);
        return preg_replace("%$re2%", '$1$2$3$4$5$6$7', $str);
    }

    /**
     * Combines and compresses all of the js files into a single cached file.
     * @param int $ttl The length of the time cache should live before being rebuilt. if 0 the cache will never expire
     */
    public static function cache_js($ttl = 0) {
        // TRICKY: processes may include different css files depending on
        // the method so we must use the request path instead of the controller name.
        // attaching the Atomar version ensures browsers get the latest cached version.
        $hash = md5(Atomar::version());
        $cache_dir = Atomar::$config['cache'] . 'performance/js/';
        $js_cache = $cache_dir . $hash . '.js';
        if (file_exists($js_cache)) {
            $cache_life = filemtime($js_cache);
        } else {
            $cache_life = false;
        }

        // rebuild the cache
        if ($cache_life === false || (time() - $cache_life > $ttl && $ttl > 0)) {
            // combine files
            $js = '';
            foreach (Templator::$js as $file) {
                $resolved_file = AssetManager::realpath($file);
                if (is_file($resolved_file)) {
                    $js .= ' ' . file_get_contents($resolved_file);
                }
            }

            // compress js
            $js = self::minify_js($js);

            // write cache
            if (!file_exists($cache_dir)) {
                $old = umask(0002);
                if (!mkdir($cache_dir, 0775, true)) {
                    Logger::log_warning('could not create the cached js directory', $cache_dir);
                }
                umask($old);
            }
            if (file_put_contents($js_cache, $js)) {
                chmod($js_cache, 0775);
                Templator::$js = array('/' . ltrim($js_cache, '/'));
            } else {
                Logger::log_error('Failed to cache the js files');
            }
        } else {
            Templator::$js = array('/' . ltrim($js_cache, '/'));
        }
    }

    // http://stackoverflow.com/questions/15195750/minify-compress-css-with-regex

    public static function minify_js($str) {
        return \JSMin::minify($str);
    }
}