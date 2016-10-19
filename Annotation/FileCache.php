<?php

namespace Adadgio\CacheBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation(
 * @Target("METHOD")
 */
class FileCache extends Annotation
{
    public $expires = '10m';

    public $enabled = true;

    public $category;

    public function isEnabled()
    {
        return $this->enabled;
    }
    
    public function getExpires()
    {
        return $this->expires;
    }

    public function getCategory()
    {
        return $this->category;
    }
}
