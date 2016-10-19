CacheBundle, integrated into Symfony cache
====

A file caching service integrated into Symfony cache

## Install

Install with composer.

`composer require adadgio/cache-bundle`

Make the following change to your `AppKernel.php` file to the registered bundles array.

```
new Adadgio\CacheBundle\AdadgioCacheBundle(),
```

## Usage

See bellow.

## Annotation

```php
/**
     * @Route("/get/documents", name="get_documents")
     * @FileCache(enabled=true, expires="6h", category="my-cache", exclusions={
     *     "body": {"from_date", "api_key"}
     * })
     * Note on cache exclusions: fields included here will not be taken in account in the cache identifier
     */
    public function getDocumentsFunction(ApiHandler $api, FileCacheService $cache)
    {
        if ($cache->isValid()) {
            $data = $cache->retrieve();
            return new Response($data); // return $cache->retrieve(); works as well
        }

        //... your logic
        $data = "Hello world. I know you'r not getting better but please hang on!";

        $cache->put(data);
        return new Response($data); // $cache->put(new Response($data));  works as well
    }
```
