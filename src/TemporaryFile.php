<?php


namespace PinaMedia;


class TemporaryFile extends File
{
    public function __destruct()
    {
        unlink($this->path);
    }
}