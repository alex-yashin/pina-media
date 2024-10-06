<?php


namespace PinaMedia\Controls;


use Pina\App;
use Pina\Controls\Wrapper;

class MediaGalleryView extends MediaGallery
{

    protected function drawInput()
    {
        $container = new Wrapper('.thumbnails');
        foreach ($this->value as $media) {
            /** @var MediaView $thumbnail */
            $thumbnail = App::make(MediaView::class);
            $thumbnail->setValue($media);
            $thumbnail->setCompact();
            $container->append($thumbnail);
        }
        return $container;
    }
}