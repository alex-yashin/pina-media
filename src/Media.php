<?php

namespace PinaMedia;

use Pina\App;
use Pina\Arr;
use Pina\Config;
use RuntimeException;

class Media
{
    protected static $allowedMimeTypes = [
        'image/*',
    ];

    public static function allowMimeType($pattern)
    {
        static::$allowedMimeTypes[] = $pattern;
    }

    public static function download($url, $fileName = null)
    {
        $tmpPath = App::tmp() . '/' . uniqid('download', true);
        $f = fopen($tmpPath, 'w');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FILE, $f);
        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
//        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_exec($ch);

        $info = curl_getinfo($ch);
        if ($info['http_code'] !== 200) {
            throw new RuntimeException('Can`t download resource: ' . $url);
        }
        curl_close($ch);

        if (empty($fileName)) {
            $urlPath = parse_url($url, PHP_URL_PATH);
            $fileName = pathinfo($urlPath, PATHINFO_BASENAME);
        }
        return new File($tmpPath, $fileName, $info['content_type'] ?? null);
    }

    /**
     * @param string $storage
     * @param string $path
     * @return string
     * @throws \Exception
     */
    public static function getUrl(string $storage, string $path)
    {
        if (empty($storage)) {
            return $path;
        }
        return static::encodePath(static::getStorage($storage)->getUrl($path));
    }

    public static function encodePath($url)
    {
        $parsed = parse_url($url);
        $parts = explode('/', $parsed['path']);
        foreach ($parts as $k => $v) {
            $parts[$k] = rawurlencode($v);
        }
        $parsed['path'] = implode('/', $parts);
        return (!empty($parsed['host']) ? $parsed['scheme'] . '://' . $parsed['host'] : '') . $parsed['path'];
    }

    public static function getStorage($storageKey)
    {
        static $storages = array();

        if (!empty($storages[$storageKey])) {
            return $storages[$storageKey];
        }

        return $storages[$storageKey] = new Storage($storageKey);
    }

    public static function getStorageConfig($storageKey, $configKey)
    {
        $config = Config::get('media');
        return $config[$storageKey][$configKey] ?? '';
    }

    /**
     * @param $mediaId
     * @param $params
     * @param string $prefix
     * @return array
     * @throws \Exception
     */
    public static function getMediaParams($mediaId, $params, $prefix = '')
    {
        $gw = MediaGateway::instance();

        $makeUrlNeedle = false;
        $urlKey = array_search('url', $params);
        if ($urlKey !== false) {
            unset($params[$urlKey]);
            $makeUrlNeedle = true;
            $gw->select('storage');
            $gw->select('path');
        }

        foreach ($params as $param) {
            $gw->select($param);
        }
        $media = $gw->find($mediaId);

        if ($makeUrlNeedle) {
            $media['url'] = Media::getUrl($media['storage'], $media['path']);
            $params[] = 'url';
        }

        $media = Arr::only($media, $params);

        if (empty($prefix)) {
            return $media;
        }

        $result = [];
        foreach ($media as $key => $val) {
            $result[$prefix . $key] = $val;
        }

        return $result;
    }

}
