<?php


namespace Packlink\BusinessLogic\FileResolver;

/**
 * Class FileResolverService
 *
 * @package Packlink\BusinessLogic\FileResolver
 */
class FileResolverService
{
    /**
     * Class name.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Array of folders where the source files should be searched for.
     *
     * @var array
     */
    protected $folders;

    /**
     * FileResolverService constructor.
     *
     * @param $folders
     */
    public function __construct($folders)
    {
        $this->folders = $folders;
    }

    /**
     * Returns merged content of the requested source file.
     *
     * @param string $sourceFile
     *
     * @return array
     */
    public function getContent($sourceFile)
    {
        $content = array();

        foreach ($this->folders as $folder) {
            $filePath = $folder . '/' . $sourceFile . '.json';

            if (!file_exists($filePath)) {
                continue;
            }

            $serializedJson = file_get_contents($filePath);

            if ($serializedJson) {
                $content[] = json_decode($serializedJson, true);
            }
        }

        return $content === array() ? $content : $this->mergeFileContent($content);
    }

    /**
     * Adds new folder to the folders array.
     *
     * @param string $folder
     */
    public function addFolder($folder)
    {
        $this->folders[] = $folder;
    }

    /**
     * Gets folders array.
     *
     * @return array
     */
    public function getFolders()
    {
        return $this->folders;
    }

    /**
     * Merges content of source files.
     *
     * @param array $content
     *
     * @return array
     */
    protected function mergeFileContent($content)
    {
        $mergedContent = array_shift($content);

        foreach ($content as $fileContent) {
            $mergedContent = $this->mergeDistinct($mergedContent, $fileContent);
        }

        return $mergedContent;
    }

    /**
     * Performs array merge similar to array_merge_recursive,
     * but instead of converting values with duplicate keys to arrays,
     * it overwrites them with the value from the second array.
     *
     * @param array $array1
     * @param array $array2
     *
     * @return mixed
     */
    protected function mergeDistinct(&$array1, &$array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->mergeDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
