<?php


namespace App\Live\Endpoints;


use Exception;
use Pina\Config;
use Pina\EmptyContent;
use Pina\Http\Endpoint;
use Pina\Input;
use Pina\Response;
use PinaMedia\Media;
use PinaMedia\MediaGateway;

/**
 * Типовой эндпоинт для проксирования медиа-файлов из хранилища
 */
class MediaEndpoint extends Endpoint
{
    /**
     * @return Response
     */
    public function index()
    {
        $storageKey = $this->location->resource('@');
        $config = Config::get('media', $storageKey);

        if (empty($config)) {
            return Response::notFound();
        }

        $fullResource = Input::getResource();

        if (strpos($fullResource, '/../') !== false) {
            return Response::badRequest()->setContent(new EmptyContent);
        }

        if (!preg_match('/^' . $storageKey . '\/(.*)/si', $fullResource, $matches)) {
            return Response::badRequest()->setContent(new EmptyContent);
        }

        $path = array_pop($matches);

        try {
            $this->readFile($storageKey, $path);
        } catch (Exception $e) {
            return Response::notFound();
        }

        exit;
    }

    /**
     * @param string $storageKey
     * @param string $path
     * @throws \League\Flysystem\FileNotFoundException
     * @throws Exception
     */
    protected function readFile(string $storageKey, string $path)
    {
        $media = MediaGateway::instance()->whereBy('storage', $storageKey)->whereBy('path', $path)->firstOrFail();

        $storage = Media::getStorage($storageKey);
        $stream = $storage->filesystem()->readStream($path);

        header("Content-type: " . $media['type']);
        header("Content-length: " . $media['size']);
        header("Cache-Control: no-cache");

        fpassthru($stream);
    }
}