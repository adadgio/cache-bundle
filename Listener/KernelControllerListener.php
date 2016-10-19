<?php

namespace Adadgio\CacheBundle\Listener;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

use Adadgio\Common\Utils\ReflectionAnalysis;
use Adadgio\CacheBundle\Annotation\FileCache;
use Adadgio\CacheBundle\Component\FileCacheService;

class KernelControllerListener
{
    /**
     * @var object \Doctrine\..\AnnotationReader
     */
    private $reader;

    /**
    * @var object \FileCacheService
     */
    private $handler;

    public function __construct(Reader $reader, FileCacheService $cache)
    {
        $this->reader = $reader;
        $this->cache = $cache;
    }
    
    /**
     * @param object \FilterControllerEvent
     * @return void
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $controller = $event->getController();

        $reflection  = new \ReflectionObject($controller[0]); // actual controller called
        $methodReflection = $reflection->getMethod($controller[1]); // actual method called

        foreach($this->reader->getMethodAnnotations($methodReflection) as $annotation) {
            if (!$annotation instanceof FileCache) {
                continue;
            }

            // note "$annotation" is an object of type \Adadgio\CacheBundle\Component\Cache\Annotation\FileCache;
            $this
                ->cache
                ->createFromRequestAndAnnotation($request, $annotation);

            // inject the FileCacheHandler object as parameter into the controller in the variable type hinted with "FileCacheHandler"
            ReflectionAnalysis::ofController($request->attributes->get('_controller'));
            $argumentName = ReflectionAnalysis::getArgumentTypeHintedWith('FileCacheService');

            if (false === $argumentName) {
                return;
            }

            if ($request->attributes->has($argumentName)) {
                return;
            }

            $request->attributes->set($argumentName, $this->cache);
        }
    }


}
