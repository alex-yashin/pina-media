<?php


namespace PinaMedia\Controls;

use Pina\Html;

class MediaView extends MediaControl
{

    protected function drawInput()
    {
        if (empty($this->value) || !is_array($this->value) || empty($this->value['url'])) {
            return '';
        }

        $img = Html::nest('.thumbnail', Html::img($this->value['url']));
        if ($this->compact) {
            return $img;
        }
        return Html::nest('.form-control image-control', $img);
    }

}