<?php

declare(strict_types=1);

namespace BnplPartners\Factoring004Magento\Helper;

use Magento\Framework\App\CacheInterface;

class CacheAdapter implements \Psr\SimpleCache\CacheInterface
{
    protected $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }
    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        $value = $this->cache->load($key);
        if (!empty($value)) {
            $data = json_decode($value, true);
            if (json_last_error() == JSON_ERROR_NONE) {
                return $data;
            }
            return $default;
        }
        return $default;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        return $this->cache->save(json_encode($value), $key, [], $ttl);
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        return $this->cache->remove($key);
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null): iterable
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function has($key): bool
    {
        return true;
    }
}
