<?php

namespace Genouest\Bundle\BiomajBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class GenouestBiomajBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return strtr(__DIR__, '\\', '/');
    }
}
