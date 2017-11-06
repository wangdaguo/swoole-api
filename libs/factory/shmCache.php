<?php
class LibShmCache
{
    private $instance;
    public function __construct()
    {
        $conf = APPPATH . '/configs/libshmcache.conf';
        if(!file_exists($conf))
        {
            throw new Swoole\Exception\Factory("config file:" . $conf . "not found");
        }
        $this->instance = new \ShmCache($conf, \ShmCache::SERIALIZER_IGBINARY);
    }

    public function get($key)
    {
        return $this->instance->get($key);
    }
    public function set($key, $value, $duration = \ShmCache::NEVER_EXPIRED)
    {
        return $this->instance->set($key, $value, $duration);
    }

    public function delete($key)
    {
        return $this->instance->delete($key);
    }

    public function stats()
    {
        return $this->instance->stats();
    }

    public function incr($key, $increment = 1, $duration = \ShmCache::NEVER_EXPIRED)
    {
        return $this->instance->incr($key, $increment, $duration);
    }

    public function flush()
    {
        return $this->instance->clear();
    }
}
return new LibShmCache();
