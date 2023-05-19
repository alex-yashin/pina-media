<?php


namespace PinaMedia\Types;


use Pina\Types\StringType;

class StorageType extends StringType
{

    public function getSize()
    {
        return 16;
    }

}