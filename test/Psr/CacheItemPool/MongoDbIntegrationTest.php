<?php
/**
 * @see       https://github.com/zendframework/zend-cache for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-cache/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Cache\Psr\CacheItemPool;

use Cache\IntegrationTests\CachePoolTest;
use Zend\Cache\Psr\CacheItemPool\CacheItemPoolDecorator;
use Zend\Cache\Storage\Adapter\MongoDb;
use Zend\Cache\Storage\Plugin\Serializer;
use Zend\Cache\StorageFactory;
use Zend\Cache\Exception;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;

class MongoDbIntegrationTest extends CachePoolTest
{
    /**
     * Backup default timezone
     * @var string
     */
    private $tz;

    /**
     * @var MongoDb
     */
    private $storage;

    protected function setUp()
    {
        if (! getenv('TESTS_ZEND_CACHE_MONGODB_ENABLED')) {
            $this->markTestSkipped('Enable TESTS_ZEND_CACHE_MONGODB_ENABLED to run this test');
        }

        // set non-UTC timezone
        $this->tz = date_default_timezone_get();
        date_default_timezone_set('America/Vancouver');

        parent::setUp();
    }

    protected function tearDown()
    {
        date_default_timezone_set($this->tz);

        if ($this->storage) {
            $this->storage->flush();
        }

        parent::tearDown();
    }

    public function createCachePool()
    {
        try {
            $storage = StorageFactory::adapterFactory('mongodb', [
                'server'     => getenv('TESTS_ZEND_CACHE_MONGODB_CONNECTSTRING'),
                'database'   => getenv('TESTS_ZEND_CACHE_MONGODB_DATABASE'),
                'collection' => getenv('TESTS_ZEND_CACHE_MONGODB_COLLECTION'),
            ]);
            $storage->addPlugin(new Serializer());

            $deferredSkippedMessage = sprintf(
                '%s storage doesn\'t support driver deferred',
                \get_class($storage)
            );
            $this->skippedTests['testHasItemReturnsFalseWhenDeferredItemIsExpired'] = $deferredSkippedMessage;

            return new CacheItemPoolDecorator($storage);
        } catch (Exception\ExtensionNotLoadedException $e) {
            $this->markTestSkipped($e->getMessage());
        } catch (ServiceNotCreatedException $e) {
            if ($e->getPrevious() instanceof Exception\ExtensionNotLoadedException) {
                $this->markTestSkipped($e->getMessage());
            }
            throw $e;
        }
    }
}
