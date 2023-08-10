<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel;

use Drupal\Cache\Adapter\DrupalAdapter;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversClass \Drupal\Cache\Adapter\DrupalAdapter
 */
class DrupalAdapterTest extends KernelTestBase
{
    private DrupalAdapter $adapter;
    private \DateTime $expiresAt;

    public function test(): void
    {
        $this->createCachedItems(['bar']);

        // Inspect cache internally.
        $rawCache = $this->getRawCache();
        $this->assertCount(1, $rawCache);
        $this->assertArrayHasKey('foo:bar', $rawCache);
        $this->assertSame('foo:bar', $rawCache['foo:bar']->cid);
        $this->assertEquals((object) ['abc' => "bar value"], unserialize($rawCache['foo:bar']->data));
        $this->assertSame($this->getExpiresAt()->getTimestamp(), $rawCache['foo:bar']->expire);
        $this->assertSame(['tag1', 'tag2'], $rawCache['foo:bar']->tags);

        // Inspect value retrieved from cache.
        $cached = $this->getAdapter()->getItem('bar');
        $this->assertTrue($cached->isHit());
        $valueFromCache = $cached->get();
        $this->assertEquals((object) ['abc' => "bar value"], $valueFromCache);

        // Test deleting one item.
        $this->getAdapter()->deleteItem('bar');
        $cached = $this->getAdapter()->getItem('bar');
        $this->assertFalse($cached->isHit());
        $this->assertFalse($this->getAdapter()->hasItem('bar'));

        // Test deleting more than one item.
        $this->createCachedItems(['bar', 'baz', 'qux']);
        $this->getAdapter()->deleteItems(['bar', 'baz']);
        $this->assertFalse($this->getAdapter()->hasItem('bar'));
        $this->assertFalse($this->getAdapter()->hasItem('baz'));
        $this->assertTrue($this->getAdapter()->hasItem('qux'));

        // Test clearing the whole backend.
        $this->createCachedItems(['bar', 'baz', 'qux']);
        $this->getAdapter()->clear();
        $this->assertFalse($this->getAdapter()->hasItem('bar'));
        $this->assertFalse($this->getAdapter()->hasItem('baz'));
        $this->assertFalse($this->getAdapter()->hasItem('qux'));

        // Test tag invalidation.
        $this->createCachedItems(['bar', 'baz', 'qux']);
        $this->getAdapter()->invalidateTags(['tag3', 'tag4']);
        $this->assertTrue($this->getAdapter()->hasItem('bar'));
        $this->assertFalse($this->getAdapter()->hasItem('baz'));
        $this->assertFalse($this->getAdapter()->hasItem('qux'));

        $this->getAdapter()->invalidateTags(['tag2']);
        $this->assertFalse($this->getAdapter()->hasItem('bar'));
        $this->assertFalse($this->getAdapter()->hasItem('baz'));
        $this->assertFalse($this->getAdapter()->hasItem('qux'));
    }

    private function getAdapter(): DrupalAdapter
    {
        if (!isset($this->adapter)) {
            $this->adapter = new DrupalAdapter($this->container->get('cache.data'), 'foo');
        }
        return $this->adapter;
    }
    private function getRawCache(): array
    {
        $staticCached = new \ReflectionProperty($this->container->get('cache.data'), 'cache');
        return $staticCached->getValue($this->container->get('cache.data'));
    }

    private function createCachedItems(array $ids = []): void
    {
        $items = [
            'bar' => ['tag1', 'tag2'],
            'baz' => ['tag1', 'tag2', 'tag3'],
            'qux' => ['tag2', 'tag3', 'tag4'],
        ];

        foreach ($ids as $id) {
            $cached = $this->getAdapter()->getItem($id)
                ->set((object) ['abc' => "$id value"])
                ->expiresAt($this->getExpiresAt())
                ->tag($items[$id]);
            $this->getAdapter()->save($cached);

            $cached = $this->getAdapter()->getItem($id);
            $this->assertTrue($this->getAdapter()->hasItem($id));
            $this->assertTrue($cached->isHit());
            $valueFromCache = $cached->get();
            $this->assertEquals((object) ['abc' => "$id value"], $valueFromCache);
        }
    }

    private function getExpiresAt(): \DateTime
    {
        if (!isset($this->expiresAt)) {
            $this->expiresAt = new \DateTime('+2 days');
        }
        return $this->expiresAt;
    }
}
