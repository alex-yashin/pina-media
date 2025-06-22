<?php


namespace PinaMedia\Endpoints;


use Pina\EmptyContent;
use Pina\Http\Endpoint;
use Pina\Input;
use PinaMedia\ImageResizer;
use PinaMedia\Media;
use Pina\Response;
use PinaMedia\MediaGateway;

class ResizeEndpoint extends Endpoint
{

    /**
     * @return Response
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function index()
    {
        $resizeStorage = $this->location()->resource('@');
        $originalStorage = Media::getStorageConfig($resizeStorage, 'original');
        if (empty($originalStorage)) {
            return Response::badRequest()->setContent(new EmptyContent);
        }
        if (Media::getStorageConfig($originalStorage, 'resize') != $resizeStorage) {
            return Response::badRequest()->setContent(new EmptyContent);
        }

        $fullResource = Input::getResource();//ltrim($this->server()->get('REQUEST_URI'), '/');

        if (strpos($fullResource, '/../') !== false) {
            return Response::badRequest()->setContent(new EmptyContent);
        }
        $schema = [
            'width' => 'w',
            'height' => 'h',
            'crop' => 'c',
            'trim' => 't',
        ];
        $pattern = '';
        foreach ($schema as $p) {
            $pattern .= '(?:' . $p . '([\d]+))?';
        }
        if (!preg_match('/^' . $resizeStorage . '\/' . $pattern . '\//si', $fullResource, $matches)) {
            return Response::badRequest()->setContent(new EmptyContent);
        }

        $base = array_shift($matches);
        $params = [];
        $keys = array_keys($schema);
        foreach ($keys as $index => $key) {
            $params[$key] = isset($matches[$index]) ? $matches[$index] : false;
        }

        $path = substr($fullResource, strlen($base));

        $exists = MediaGateway::instance()
            ->whereBy('storage', $originalStorage)
            ->whereBy('path', $path)
            ->exists();

        if (!$exists) {
            return Response::badRequest()->setContent(new EmptyContent);
        }

        $storage = Media::getStorage($originalStorage);
        $file = $storage->getLocalFile($path);

        $source = $file->getPath();
        if (!file_exists($source)) {
            return Response::notFound();
        }

        $target = null;
        $resizeRoot = Media::getStorageConfig($resizeStorage, 'root');
        if ($resizeRoot) {
            $target = rtrim($resizeRoot, '/') . '/' . $fullResource;
            $targetDir = dirname($target);
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
        }
        $ir = new ImageResizer($params['width'], $params['height'], $params['crop'], $params['trim']);
        $ir->resize($source, $target);
        if (empty($target)) {
            //при пустом $target resize делает вывод картинки сразу в stdout
            exit;
        }

        if (!file_exists($target)) {
            return Response::internalError()->emptyContent();
        }

        header('Content-type: ' . $ir->getMime());
        $ir->outCacheHeaders(315360000);
        readfile($target);
        exit;
    }

}