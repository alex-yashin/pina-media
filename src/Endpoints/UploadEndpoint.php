<?php


namespace PinaMedia\Endpoints;


use Pina\App;
use Pina\BadRequestException;
use Pina\Http\Endpoint;
use Pina\Response;
use PinaMedia\Uploader;
use PinaMedia\Media;
use PinaMedia\MediaGateway;
use RuntimeException;

class UploadEndpoint extends Endpoint
{
    /**
     * @return Response
     * @throws \Exception
     */
    public function store($id)
    {
        App::forceMimeType("application/json");
        try {
            $uploader = new Uploader();
            $mediaIds = $uploader->save();
        } catch (RuntimeException $e) {
            throw new BadRequestException($e->getMessage());
        }

        $ms = MediaGateway::instance()->whereId($mediaIds)->get();
        foreach ($ms as $k => $m) {
            $ms[$k]['url'] = Media::getUrl($m['storage'], $m['path']);
        }

        return Response::ok()->json($id == 'single' && count($ms) ? $ms[0] : $ms);
    }
}