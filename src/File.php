<?php

namespace PinaMedia;

use Exception;
use Pina\Config;
use RuntimeException;

class File
{

    protected $path = null;
    protected $meta = [];

    public function __construct($path, $name, $mime = null, $title = null)
    {
        $this->path = $path;
        if (!file_exists($this->path)) {
            throw new RuntimeException('File does not exist: ' . $this->path);
        }
        $this->meta = [];
        $this->meta += $this->getImageProperties($this->path, $mime);
        $this->meta += $this->getFileProperties($this->path);
        $this->meta['path'] = $this->generatePath($name);
        $this->meta['title'] = $title ?? '';
    }

    /**
     * @param string $storageKey
     * @throws Exception
     */
    public function moveToStorage($storageKey = '')
    {
        $this->saveToStorage($storageKey);
        $this->unlink();
    }

    /**
     * @param string $storageKey
     * @throws \League\Flysystem\FileExistsException
     * @throws \Exception
     */
    public function saveToStorage($storageKey = '')
    {
        if (empty($storageKey)) {
            $storageKeyConfig = Config::get('media', 'default');
            if (!$storageKeyConfig) {
                throw new Exception('Storage is not configured');
            }
            $storageKey = $storageKeyConfig;
        }

        $this->meta['storage'] = $storageKey;

        $storage = Media::getStorage($storageKey);

        $stream = fopen($this->path, 'r+');
        $storage->filesystem()->writeStream($this->meta['path'], $stream, [
            'CacheControl' => 'public, max-age=31536000',
        ]);
        fclose($stream);
    }

    public function unlink()
    {
        unlink($this->path);
        $this->path = null;
    }

    public function exists()
    {
        return file_exists($this->path);
    }

    public function saveMeta()
    {
        return MediaGateway::instance()->insertGetId($this->meta);
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getImageWidth()
    {
        return $this->meta['width'] ?? null;
    }

    public function getImageHeight()
    {
        return $this->meta['height'] ?? null;
    }

    public function getMimeType()
    {
        return $this->meta['type'] ?? null;
    }

    public function isMimeType($type)
    {
        $current = explode('/', $this->meta['type'] ?? "");
        $needle = explode('/', $type);
        foreach ($needle as $k => $item) {
            if ($item == '*') {
                continue;
            }

            if ($item != ($current[$k] ?? '')) {
                return false;
            }
        }
        return true;
    }

    public function getSize()
    {
        return $this->meta['size'] ?? null;
    }

    public function getHash()
    {
        return $this->meta['hash'] ?? null;
    }

    public function getStorageKey()
    {
        return $this->meta['storage'] ?? null;
    }

    public function getStoragePath()
    {
        return $this->meta['path'] ?? null;
    }

    protected function getFileProperties($path)
    {
        return [
            'size' => filesize($path),
            'hash' => md5_file($path),
        ];
    }

    protected function getImageProperties($path, $type)
    {
        $info = getimagesize($path);
        if (empty($info)) {
            $info = [];
        }

        if (empty($info['mime'])) {
            $info['mime'] = mime_content_type($path);
        }

        /*
          if (empty($info['mime'])) {
          $pathInfo = pathinfo($name);
          $info['mime'] = MimeTypes::resolveMimeType($pathInfo['extension']);
          }
         */

        if (empty($info['mime'])) {
            $info['mime'] = $type;
        }

        return [
            'width' => $info[0] ?? 0,
            'height' => $info[1] ?? 0,
            'type' => $info['mime'] ?? '',
        ];
    }

    protected function generatePath($name)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $code = "";
        $length = mt_rand(8, 32);

        $clen = strlen($chars) - 1;
        while (strlen($code) < $length) {
            $code .= $chars[mt_rand(0, $clen)];
        }

        $ext = pathinfo($name, PATHINFO_EXTENSION);
        if (empty($ext) && !empty($this->meta['type'])) {
            $ext = MimeTypes::resolveExtension($this->meta['type']);
        }
        $basename = pathinfo($name, PATHINFO_FILENAME);

//        $token = $code . '.' . mt_rand();
        $token = $code;

        $dir = substr($token, 0, 2) . '/' . substr($token, 2, 2) . '/';
        $filename = $this->normalizeName($basename) . '.' . substr($token, 4) . ($ext ? ('.' . $ext) : '');

        return $dir . $filename;
    }

    protected function normalizeName($name)
    {
        return str_replace(' ', '-', $name);
    }

}
