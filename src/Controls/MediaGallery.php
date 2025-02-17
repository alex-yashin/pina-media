<?php


namespace PinaMedia\Controls;


use Pina\App;

class MediaGallery extends MediaControl
{

    protected function makeScriptContent()
    {
        App::assets()->addScript('vendor/sortable.min.js');//https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js
        $encoded = json_encode($this->value, JSON_UNESCAPED_UNICODE);
        $sortable = "new Sortable($this->tagId.querySelector('.thumbnails'), {animation: 150,ghostClass: 'blue-background-class'})";
        return "MultipleMediaControl('#" . $this->tagId . "', '" . $this->name . "[]', " . $encoded . ");" . $sortable;
    }

}