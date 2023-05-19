<?php


namespace PinaMedia\Types;

use Pina\Types\StringType;

class MimeType extends StringType
{

    public function getSize()
    {
        return 127;
    }

}