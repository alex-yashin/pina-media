<?php


namespace PinaMedia\Controls;


use Pina\Controls\Control;
use Pina\Html;

class MediaView extends Control
{
    protected $url = '';
    protected $type = '';

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    protected function draw()
    {
        return Html::nest('.thumbnail', Html::img($this->url));
    }

}