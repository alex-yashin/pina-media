<?php


namespace PinaMedia\Controls;


use Pina\Controls\Control;
use Pina\Html;

class MediaView extends Control
{
    protected $url = '';

    public function setUrl($url)
    {
        $this->url = $url;
    }

    protected function draw()
    {
        return Html::nest('.thumbnail', Html::img($this->url));
    }

}