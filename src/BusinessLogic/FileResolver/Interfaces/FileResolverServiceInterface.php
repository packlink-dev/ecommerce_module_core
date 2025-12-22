<?php

namespace Packlink\BusinessLogic\FileResolver\Interfaces;

interface FileResolverServiceInterface
{
    /**
     * Returns merged content of the requested source file.
     *
     * @param string $sourceFile
     *
     * @return array
     */
    public function getContent($sourceFile);

    /**
     * Adds new folder to the folders array.
     *
     * @param string $folder
     *
     * @return void
     */
    public function addFolder($folder);

    /**
     * Gets folders array.
     *
     * @return array
     */
    public function getFolders();

}