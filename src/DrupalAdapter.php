<?php

declare(strict_types=1);

namespace Drupal\Cache\Adapter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\Cache\Adapter\AbstractTagAwareAdapter;
use Symfony\Component\Cache\CacheItem;

class DrupalAdapter extends AbstractTagAwareAdapter
{
    public function __construct(
        private CacheBackendInterface $cacheBackend,
        string $namespace = '',
        int $defaultLifetime = 0,
    ) {
        parent::__construct($namespace, $defaultLifetime);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch(array $ids): iterable
    {
        $return = [];
        /** @var \stdClass $item */
        foreach ($this->cacheBackend->getMultiple($ids) as $item) {
            $return[$item->cid]['value'] = $item->data;
        }
        return $return;
    }

    /**
     * {@inheritdoc}
     */
    protected function doHave(string $id): bool
    {
        return (bool) $this->cacheBackend->get($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doClear(string $namespace): bool
    {
        try {
            $this->cacheBackend->deleteAll();
            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete(array $ids): bool
    {
        try {
            $this->cacheBackend->deleteMultiple($ids);
            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave(array $values, int $lifetime, array $addTagData = [], array $removeTagData = []): array
    {
        // Drupal uses -1 as to mark cached data as "cached permanently".
        // @see \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT
        $expire = $lifetime === 0 ? -1 : (time() + $lifetime);
        $items = [];
        foreach ($values as $id => $value) {
            $items[$id] = [
              'data' => $value['value'],
              'expire' => $expire,
              'tags' => $value['tags'],
            ];
        }
        $this->cacheBackend->setMultiple($items);
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags): bool
    {
        if (!$tags) {
            return false;
        }
        try {
            Cache::invalidateTags($tags);
            return true;
        } catch (\Exception $e) {
            CacheItem::log($this->logger, 'Failed to invalidate tags: ' . $e->getMessage(), [
                'exception' => $e,
                'cache-adapter' => get_debug_type($this),
            ]);
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doInvalidate(array $tagIds): bool
    {
        // This method is required but not called in ::invalidateTags.
        // @see ::invalidateTags
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeleteTagRelations(array $tagData): bool
    {
        return true;
    }
}
