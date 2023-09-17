<?php

namespace PinaMedia\Controls;

use Pina\App;
use Pina\Controls\Control;
use Pina\CSRF;
use Pina\Html;

use function Pina\__;

class MediaControl extends Control
{
    protected $tagId = '';
    protected $name = '';
    protected $media = [];

    public function __construct()
    {
        $this->tagId = uniqid('im');
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param array $media
     */
    public function setMedia($media)
    {
        $this->media = $media;
    }

    /**
     * @return string
     */
    public function draw(): string
    {
        $this->includeScripts();
        return $this->drawInnerBefore() . $this->drawInner() . $this->drawInnerAfter();
    }

    protected function drawInner()
    {
        return $this->drawControl();
    }

    protected function drawControl(): string
    {
        $options = [
            'id' => $this->tagId,
            'class' => 'image-control form-control',
            'data-resource' => 'admin/en/upload',
        ];
        $options += CSRF::tagAttributeArray('post');
        $content = $this->drawSpinner() . $this->drawThumbnails() . $this->drawButton();
        return Html::tag('div', $content, $options);
    }

    protected function drawThumbnails(): string
    {
        return Html::tag('div', '', ['class' => 'thumbnails']);
    }

    protected function drawSpinner(): string
    {
        $faSpinner = Html::tag('span', '', ['class' => 'fa fa-spinner fa-pulse fa-5x fa-fw']);
        return Html::tag('div', $faSpinner, ['class' => 'spinner text-center d-none']);
    }

    protected function drawButton(): string
    {
        $button = Html::tag(
            'button',
            __('Нажмите, чтобы загрузить файл'),
            ['class' => 'btn btn-secondary mt-2 media-upload-button action-upload-image']
        );
        $message = Html::tag('div', '', ['class' => 'message text-center']);
        return $button . $message;
    }

    protected function includeScripts()
    {
        App::assets()->addScript('/media-control.js');
        App::assets()->addStyle('/media-control.css');

        App::assets()->addScriptContent('<script>' . $this->makeScriptContent() . '</script>');
    }

    protected function makeScriptContent()
    {
        $encoded = json_encode($this->media, JSON_UNESCAPED_UNICODE);
        return "SingleMediaControl('#" . $this->tagId . "', '" . $this->name . "', " . $encoded . ");";
    }

}
