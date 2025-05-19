<?php


namespace PinaMedia\Controls;

use Pina\App;
use Pina\Controls\LinkedButton;
use Pina\Html;

class MediaView extends MediaStaticFormControl
{

    protected function draw()
    {
        if (empty($this->value) || empty($this->value['url'])) {
            return '';
        }

        $type = $this->value['type'] ?? '';
        $url = $this->value['url'] ?? '';
        $title = $this->value['title'] ?? '';

        if ($type == 'application/pdf')
        {
            return Html::tag('iframe', '', ['src' => $url, 'style' => 'width:100%; min-height: 90vh;']);
        }

        $parts = explode('/', $type);
        if ($parts[0] == 'image') {
            return Html::a(Html::img($url), $url, ['class' => 'thumbnail', 'target' => '_blank']);
        }

        if ($parts[0] == 'video') {
            return Html::zz('video[controls](source[src=%][type=%])', $url, $type);
        }

        if (empty($title)) {
            $path = parse_url($url, PHP_URL_PATH);
            $filename = pathinfo($path, PATHINFO_FILENAME);
            $title = strstr($filename, '.', true) . '" ('. $type.')';
        }

        /** @var LinkedButton $button */
        $button = App::make(LinkedButton::class);
        $button->setTitle('Download "' . $title);
        return $button->setLink($url);
    }

}