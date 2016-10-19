<?php

namespace Adadgio\CacheBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation(
 * @Target("METHOD")
 */
class FileCache
{
    public $expires = '10m';

    public $enabled = true;

    public $category;
    
    public $exclusions = array('GET' => array(), 'POST' => array());

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

    public function getExclusions()
    {
        return $this->exclusions;
    }
}
