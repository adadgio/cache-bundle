<?php

namespace Adadgio\CacheBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Adadgio\CacheBundle\Annotation\FileCache;

class FileCacheService
{
    private $root;
    private $dir;
    private $env;
    private $ext;
    private $filepath;
    private $expires;

    public function __construct($environment, $kernelCacheDir)
    {
        $this->ext = '.cache';
        $this->root = $kernelCacheDir.'/adadgio/cache-bundle';

        $this->dir = null;
        $this->filepath = null;
        $this->env = $environment;
        $this->expires = '60s'; // should a default really be set
    }

    /**
     * A special method to be able to use this service from annotations.
     * See KernelControllerListener::onKernelController() method for more information.
     *
     * @param \Request A Symfony request
     * @param array Annotation
     * @return \FileCacheService
     */
    public function createFromRequestAndAnnotation(Request $request, FileCache $annotation)
    {
        $uri = $request->getRequestUri();
        $method = $request->getMethod();
        $reqParams = $this->getRequestParams($request);
        $cacheParams = array();

        // create a identifier from request url and get parameters
        // but remove optional boyd/get/post parameters when specified in
        // in annotation when creating the unique identifier

        // methodType should be get|post (see FileCache annotation), post handles only
        // body parameters, not form url encoded params
        foreach ($reqParams as $name => $value) {
            $excludedParamsNames = $annotation->getExclusions()[$method];

            if (false === in_array($name, $excludedParamsNames)) {
                $cacheParams[$name] = $value;
            }
        }

        $identifier = $uri.implode(':', $cacheParams);

        // bootstrap the hereby cache service
        $this->identify($identifier, $annotation->getCategory())->expires($annotation->getExpires());

        return $this;
    }

    /**
     * Writes a cache file into the current active cache dir.
     * @param  mixed Data to cache, any type (its normalized into a string)
     * @return \FileCacheService
     */
    public function put($data)
    {
        // only creates the directory if it does not exist
        $this->createCacheDir();

        // turn any non-string data into a string
        file_put_contents($this->filepath, serialize($data));

        return $this;
    }

    /**
     * Set a unique identifier for the active current cache file
     * and/or an optional cache sub directory (known as category).
     *
     * @param  string An clean string for the catgegory sub dir
     * @param  string Any string, its hashed anyway
     * @return \FileCacheService
     */
    public function identify($identifier, $category = null)
    {
        $hash = md5($identifier);

        $this->setCategory($category); // must be called prior to file path!
        $this->filepath = $this->dir.'/'.$hash.$this->ext;

        return $this;
    }

    /**
     * Sets an optional sub directory in which to store future cached files.
     * @param  string Subdirectory relative path
     * @return \FileCacheService
     */
    public function setCategory($category = null)
    {
        $this->dir = (null === $category) ? $this->root : $this->root.'/'.trim($category, '/');

        return $this;
    }

    /**
     * Get the current active file cached contents.
     *
     * @return string
     */
    public function retrieve()
    {
        return is_file($this->filepath) ? unserialize(file_get_contents($this->filepath)) : null;
    }

    /**
     * Sets the cache validity time span (expiration time). Possible
     * input values are "1s|2m|3h|4d" for seconds, minutes, hours or days
     *
     * @param string Expiration time prefixed by units (s, h, d)
     * @return \FileCacheService
     */
    public function expires($expression)
    {
        $units = array('s', 'm', 'h', 'd'); // seconds, minutes, hours, days

        if(!preg_match('~[0-9]{1,}[a-z]{1}~iU', $expression)) {
            return $this;
        }

        $this->expires = $expression;

        return $this;
    }

    /**
     * Tells you if the current active cached file is
     * still valid based on its expiration date.
     *
     * @return string
     */
    public function  isValid()
    {
        // a non-existing file is never valid (ex. 1st time you cache something)
        if (false === is_file($this->filepath)) {
            return false;
        }

        if ((time() - filemtime($this->filepath)) > $this->toSeconds($this->expires)) {
            $this->clear();
            return false;
        } else {
            return true;
        }
    }

    /**
     * Clears all cache data for the active given directory.
     * @return \FileCacheService
     */
    public function clear()
    {
        if (false === $this->dir) {
            return $this;
        }

        $finder = new Finder();
        $finder->files()->in($this->dir)->name('*.cache');
        foreach ($finder as $file) {
            unlink($file);
        }

        // we can delete the parent top directory if its empty
        if (count(glob(sprintf('%s/*', $this->dir))) === 0 ) {
            rmdir($this->dir);
        }

        return $this;
    }

    /**
     * Clears all cache data for the active given directory.
     * @return \FileCacheService
     */
    public function flush($category = null)
    {

    }

    public function getCacheDir()
    {
        return $this->dir;
    }

    private function createCacheDir()
    {
        if (false === is_dir($this->dir)) {
            $fs = (new Filesystem())->mkdir($this->dir);
        }
    }

    public function getCacheRootDir()
    {
        return $this->root;
    }

    /**
     * @return string
     */
    public function getCacheFilePath()
    {
        return $this->filepath;
    }

    /**
     * Turns any "3d|60d|etc" expression format into a number of seconds.
     *
     * @param string Input format "3d|60dÓ...""
     * @return integer Number of seconds
     */
    private function toSeconds($expression)
    {
        // turn the expression into seconds
        preg_match('~([0-9]{1,})([a-z]{1})~iU', $expression, $match);

        $time = $match[1];
        $unit = $match[2];

        switch($unit) {
            case 's':
                $seconds = $time;
            break;
            case 'm':
                $seconds = ($time * 60);
            break;
            case 'h':
                $seconds = ($time * 60 * 60);
            break;
            case 'd':
                $seconds = ($time * 60 * 60) * 24;
            break;
            default:
                $seconds = $this->expires;
            break;
        }

        return (int) $seconds;
    }

    /**
     * Retrieve request parameters, either in the JSON body or the GET parameters
     * depending on the request method that is detected
     *
     * @param object \Request
     * @return array
     */
    private function getRequestParams(Request $request)
    {
        if ($request->getMethod() === 'GET') {
            return $request->query->all();

        } else if ($request->getMethod() === 'POST') {
            $params = json_decode($request->getContent(), true);
            return (null === $params) ? array() : $params;

        } else {
            return array();
        }
    }
}
