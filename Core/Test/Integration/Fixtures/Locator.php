<?php

namespace MageOS\NetSuiteConnector\Core\Test\Integration\Fixtures;

/**
 * Class Locator -
 * @SuppressWarnings(PHPMD)
 */
class Locator
{
    private const FINAL_FIXTURE_DIR = '_final/';

    public static function getPath()
    {
        // cant use realpath because this folder might not exists yet!
        return __DIR__ . "/../" . self::FINAL_FIXTURE_DIR;
    }

    public static function copy($sourcePath, $files = [])
    {
        $targetPath = self::getPath();

        foreach ($files as $filePath) {
            $folder = dirname($filePath);
            if (!file_exists($targetPath . $folder)) {
                mkdir($targetPath.$folder, 0777, true);
            }

            if (file_exists($targetPath . $filePath)) {
                if (md5_file($targetPath . $filePath) != md5_file($sourcePath . $filePath)) {
                    unlink($targetPath . $filePath);
                }
            }

            if (!file_exists($targetPath . $filePath)) {
                copy($sourcePath . $filePath, $targetPath . $filePath);
            }
            // check for rollback just in case it was not included into the list
            $filePath = str_replace(".php", "_rollback.php", $filePath);

            if (file_exists($sourcePath . $filePath)) {
                if (file_exists($targetPath . $filePath)) {
                    if (md5_file($targetPath . $filePath) != md5_file($sourcePath . $filePath)) {
                        unlink($targetPath . $filePath);
                    }
                }
                if (!file_exists($targetPath . $filePath)) {
                    copy($sourcePath . $filePath, $targetPath . $filePath);
                }
            }
        }
    }
}
