<?php

namespace PinaMedia\Model;

use PinaMedia\Storages;

class Media
{

    protected $storage = '';
    protected $path = '';

    public function __construct(string $storage, string $path)
    {
        $this->storage = $storage;
        $this->path = $path;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getUrl(): string
    {
        if (empty($this->storage)) {
            return $this->path;
        }
        return $this->encodeUrl(Storages::get($this->storage)->getUrl($this->path));
    }

    protected function encodeUrl($url): string
    {
        $parsed = parse_url($url);
        $parts = explode('/', $parsed['path']);
        foreach ($parts as $k => $v) {
            $parts[$k] = rawurlencode($v);
        }
        $parsed['path'] = implode('/', $parts);
        return (!empty($parsed['host']) ? $parsed['scheme'] . '://' . $parsed['host'] : '') . $parsed['path'];
    }

}