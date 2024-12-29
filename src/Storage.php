<?php

namespace PinaMedia;

use Exception;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\AwsS3v3\AwsS3Adapter as S3Adapter;
use Aws\S3\S3Client;
use League\Flysystem\AdapterInterface;
use Pina\App;
use Pina\Arr;
use Pina\Config;
use RuntimeException;

class Storage
{

    protected $config = [];
    protected $filesystem = null;

    public function __construct($targetStorageKey = null)
    {
        $config = Config::get('media');
        $storageKey = $targetStorageKey ?? $config['default'];

        if (!isset($config[$storageKey])) {
            throw new RuntimeException('Wrong target storage');
        }

        $this->config = $config[$storageKey];
    }

    /**
     * @return Flysystem
     * @throws Exception
     */
    public function filesystem()
    {
        if (empty($this->filesystem)) {
            $adapter = $this->resolveAdapter($this->config);
            $this->filesystem = $this->createFlysystem($adapter, $this->config);
        }
        return $this->filesystem;
    }

    /**
     * @return AdapterInterface
     * @throws Exception
     */
    public function adapter()
    {
        return $this->filesystem()->getAdapter();
    }

    /**
     * @param array $config
     * @return LocalAdapter|S3Adapter
     * @throws Exception
     */
    public function resolveAdapter(array $config)
    {
        if (empty($config['driver'])) {
            throw new Exception('Missed configuration: driver');
        }

        switch ($config['driver']) {
            case 'local': return $this->createLocalDriver($config);
            case 's3': return $this->createS3Driver($config);
        }

        throw new Exception('Can`t create filesystem adapter.');
    }

    protected function createLocalDriver(array $config)
    {
        $permissions = $config['permissions'] ?? [];
        $links = ($config['links'] ?? null) === 'skip' ? LocalAdapter::SKIP_LINKS : LocalAdapter::DISALLOW_LINKS;
        return new LocalAdapter($config['root'], LOCK_EX, $links, $permissions);
    }

    protected function createS3Driver(array $config)
    {
        $s3Config = $this->formatS3Config($config);
        $root = $s3Config['root'] ?? null;
        $options = $config['options'] ?? [];
        return new S3Adapter(new S3Client($s3Config), $s3Config['bucket'], $root, $options);
    }

    protected function formatS3Config(array $config)
    {
        $config += ['version' => 'latest'];
        if ($config['key'] && $config['secret']) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }
        return $config;
    }

    /**
     * @param $path
     * @return File|TemporaryFile
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function getLocalFile($path)
    {
        $fileName = pathinfo($path, PATHINFO_FILENAME);
        if ($this->config['driver'] == 'local' && !empty($this->config['root'])) {
            $localPath = $this->concatPathToUrl($this->config['root'], $path);
            return new File($localPath, $fileName);
        }
        try {
            $readStream = $this->filesystem()->readStream($path);
        } catch (FileNotFoundException $e) {
            //TODO: поменять логику экранирования пути перед записью в БД и удалить эту строку
            $readStream = $this->filesystem()->readStream(str_replace(' ', '+', $path));
        }
        $tmpPath = App::tmp() . '/' . uniqid('local', true);
        $writeStream = fopen($tmpPath, 'w');
        stream_copy_to_stream($readStream, $writeStream);
        fclose($writeStream);
        return new TemporaryFile($tmpPath, $fileName);
    }

    /**
     * @param $path
     * @return string
     * @throws Exception
     */
    public function getUrl($path)
    {
        switch ($this->config['driver']) {
            case 'local': return $this->getLocalDriverUrl($path);
            case 's3': return $this->getS3DriverUrl($path);
        }
        throw new RuntimeException('This driver does not support retrieving URLs.');
    }

    public function getLocalDriverUrl(string $path)
    {
        return $this->concatPathToUrl($this->config['url'] ?? '', $path);
    }

    /**
     * @param string $path
     * @return string
     * @throws Exception
     */
    public function getS3DriverUrl(string $path)
    {
        $adapter = $this->adapter();
        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if (is_null($this->config['url'])) {
            throw new Exception("Missed url configuration");
        }
        return $this->concatPathToUrl($this->config['url'], $path);
    }

    protected function concatPathToUrl($url, $path)
    {
        return rtrim($url, '/') . '/' . ltrim($path, '/');
    }

    protected function createFlysystem(AdapterInterface $adapter, array $config)
    {
        $config = Arr::only($config, ['visibility', 'disable_asserts', 'url']);

        return new Flysystem($adapter, count($config) > 0 ? $config : null);
    }

    public static function getTmpPath(): string
    {
        return App::tmp();
    }

}