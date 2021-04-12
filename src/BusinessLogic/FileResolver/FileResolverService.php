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

        return $content === array() ? $content : call_user_func_array('array_replace_recursive', $content);
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
}
