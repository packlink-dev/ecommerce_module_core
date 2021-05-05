<?php

namespace Packlink\DemoUI\Lib;

use RuntimeException;

class Composer
{
    public static function postUpdate()
    {
        $fromBase = __DIR__ . '/../../BusinessLogic/Resources/';
        $toBasePacklink = __DIR__ . '/../src/Views/PRO/resources/';
        $toBaseAcme = __DIR__ . '/../src/Views/ACME/resources/';

        static::copy(array(
            $fromBase . 'js' => $toBasePacklink . 'js',
            $fromBase . 'css' => $toBasePacklink . 'css',
            $fromBase . 'images' => $toBasePacklink . 'images',
        ));

        static::copy( array(
            $fromBase . 'js' => $toBaseAcme . 'js',
            $fromBase . 'css' => $toBaseAcme . 'css',
            $fromBase . 'images' => $toBaseAcme . 'images',
        ));
    }

    /**
     * Copies directories according to data provided by $map parameter.
     *
     * @param array $map
     */
    private static function copy($map)
    {
        foreach ($map as $from => $to) {
            self::copyDirectory($from, $to);
        }
    }

    /**
     * Copies directory.
     *
     * @param string $src
     * @param string $dst
     */
    private static function copyDirectory($src, $dst)
    {
        $dir = opendir($src);
        self::mkdir($dst);

        while (false !== ($file = readdir($dir))) {
            if (($file !== '.') && ($file !== '..')) {
                if (is_dir($src . '/' . $file)) {
                    self::mkdir($dst . '/' . $file);

                    self::copyDirectory($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }

        closedir($dir);
    }

    /**
     * Creates directory.
     *
     * @param string $destination
     */
    private static function mkdir($destination)
    {
        if (!file_exists($destination) && !mkdir($destination) && !is_dir($destination)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $destination));
        }
    }
}