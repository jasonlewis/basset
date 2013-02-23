<?php namespace JasonLewis\Basset\Compiler;

use JasonLewis\Basset\Collection;
use JasonLewis\Basset\Exceptions\NoAssetsCompiledException;

class StringCompiler extends Compiler {

    /**
     * Compile the assets of a collection.
     *
     * @param  JasonLewis\Basset\Collection  $collection
     * @return array
     */
    public function compile(Collection $collection, $group)
    {
        $collection->processCollection();

        // We'll store each of the assets compiled response in an array so that we can join each
        // response by a new line with implode.
        $responses = array();

        foreach ($collection->getAssets($group) as $asset)
        {
            // If remote assets are not to be compiled and the asset is remote or the asset is being
            // ignored then it won't be included.
            if ($asset->isIgnored() or ($asset->isRemote() and ! $this->config->get('basset::compile_remotes', false)))
            {
                continue;
            }

            $responses[$asset->getRelativePath()] = $asset->compile();
        }

        // If no assets were compiled then we'll throw an exception.
        if (empty($responses))
        {
            throw new NoAssetsCompiledException("No [{$group}] assets compiled for collection [{$collection->getName()}]");
        }

        return $responses;
    }

}