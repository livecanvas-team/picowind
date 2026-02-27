<?php

namespace PicowindDeps\Timber\Cache;

use PicowindDeps\Timber\Loader;
/**
 * Class Cleaner
 *
 * @api
 */
class Cleaner
{
    public static function clear_cache(string $mode = 'all'): bool
    {
        switch ($mode) {
            case 'all':
                $twig_cache = self::clear_cache_twig();
                $timber_cache = self::clear_cache_timber();
                if ($twig_cache && $timber_cache) {
                    return \true;
                }
                break;
            case 'twig':
                return self::clear_cache_twig();
            case 'timber':
                return self::clear_cache_timber();
        }
        return \false;
    }
    /**
     * Clears Timber’s cache.
     *
     * @api
     * @since 2.0.0
     * @example
     * ```php
     * Timber\Cache\Cleaner::clear_cache_timber();
     * ```
     *
     * @return bool
     */
    public static function clear_cache_timber()
    {
        $loader = new Loader();
        return $loader->clear_cache_timber();
    }
    /**
     * Clears Twig’s cache.
     *
     * @api
     * @since 2.0.0
     * @example
     * ```php
     * Timber\Cache\Cleaner::clear_cache_twig();
     * ```
     *
     * @return bool
     */
    public static function clear_cache_twig()
    {
        $loader = new Loader();
        return $loader->clear_cache_twig();
    }
    protected static function delete_transients_single_site()
    {
        global $wpdb;
        $sql = "\n                DELETE\n                    a, b\n                FROM\n                    {$wpdb->options} a, {$wpdb->options} b\n                WHERE\n                    a.option_name LIKE '%_transient_%' AND\n                    a.option_name NOT LIKE '%_transient_timeout_%' AND\n                    b.option_name = CONCAT(\n                        '_transient_timeout_',\n                        SUBSTRING(\n                            a.option_name,\n                            CHAR_LENGTH('_transient_') + 1\n                        )\n                    )\n                AND b.option_value < UNIX_TIMESTAMP()\n            ";
        return $wpdb->query($sql);
    }
    protected static function delete_transients_multisite()
    {
        global $wpdb;
        $sql = "\n                    DELETE\n                        a, b\n                    FROM\n                        {$wpdb->sitemeta} a, {$wpdb->sitemeta} b\n                    WHERE\n                        a.meta_key LIKE '_site_transient_%' AND\n                        a.meta_key NOT LIKE '_site_transient_timeout_%' AND\n                        b.meta_key = CONCAT(\n                            '_site_transient_timeout_',\n                            SUBSTRING(\n                                a.meta_key,\n                                CHAR_LENGTH('_site_transient_') + 1\n                            )\n                        )\n                    AND b.meta_value < UNIX_TIMESTAMP()\n                ";
        $clean = $wpdb->query($sql);
        return $clean;
    }
    public static function delete_transients()
    {
        global $_wp_using_ext_object_cache;
        if ($_wp_using_ext_object_cache) {
            return 0;
        }
        global $wpdb;
        $records = 0;
        // Delete transients from options table
        $records .= self::delete_transients_single_site();
        // Delete transients from multisite, if configured as such
        if (\is_multisite() && \is_main_network()) {
            $records .= self::delete_transients_multisite();
        }
        return $records;
    }
}
