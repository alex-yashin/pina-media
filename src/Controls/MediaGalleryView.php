<?php


namespace PinaMedia\Controls;


use Pina\App;
use Pina\Controls\Control;
use Pina\Controls\Wrapper;

class MediaGalleryView extends Control
{
    protected $urls = [];

    public function setUrls(array $urls)
    {
        $this->urls = $urls;
        return $this;
    }

    protected function draw()
    {
        $container = new Wrapper('.thumbnails');
        foreach ($this->urls as $url) {
            /** @var MediaView $thumbnail */
            $thumbnail = App::make(MediaView::class);
            $thumbnail->setUrl($url);
            $container->append($thumbnail);
        }
        return $container;
    }
}