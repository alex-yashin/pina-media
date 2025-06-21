<?php

namespace PinaMedia\Controls;

use Pina\App;
use Pina\Controls\FormInput;
use Pina\CSRF;
use Pina\Html;

use function Pina\__;

class MediaControl extends FormInput
{
    protected $tagId = '';
    protected $name = '';

    public function __construct()
    {
        $this->tagId = uniqid('im');
    }

    protected function drawInput()
    {
        $this->includeScripts();
        $options = [
            'id' => $this->tagId,
            'class' => 'image-control form-control',
            'data-resource' => 'upload',
        ];
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
        $button = Html::a(
            '📎',
            '#',
            ['class' => 'btn btn-secondary btn-attachment mt-2 media-upload-button action-upload-image']
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
        $encoded = json_encode($this->value, JSON_UNESCAPED_UNICODE);
        return "SingleMediaControl('#" . $this->tagId . "', '" . $this->name . "', " . $encoded . ");";
    }

}
