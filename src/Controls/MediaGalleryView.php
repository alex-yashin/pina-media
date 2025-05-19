<?php


namespace PinaMedia\Controls;


use Pina\App;
use Pina\Controls\Wrapper;

class MediaGalleryView extends MediaStaticFormControl
{
    protected function draw()
    {
        if (empty($this->value) || !is_array($this->value)) {
            return '';
        }

        $container = new Wrapper('.thumbnails attachments');
        foreach ($this->value as $line) {
            /** @var MediaView $view */
            $view = App::make(MediaView::class);
            $view->setValue($line);
            $container->append($view);
        }
        return $container;
    }
}