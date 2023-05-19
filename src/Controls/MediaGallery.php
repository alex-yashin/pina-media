<?php


namespace PinaMedia\Controls;


use Pina\StaticResource\Script;

class MediaGallery extends MediaControl
{

    protected function makeScriptContent()
    {
        $this->resources()->append((new Script())->setSrc('https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js'));
        $encoded = json_encode($this->media, JSON_UNESCAPED_UNICODE);
        $sortable = "new Sortable($this->tagId.querySelector('.thumbnails'), {animation: 150,ghostClass: 'blue-background-class'})";
        return "MultipleMediaControl('#" . $this->tagId . "', '" . $this->name . "[]', " . $encoded . ");".$sortable;
    }

}