<?php


namespace PinaMedia\Types;

use Pina\Types\StringType;

class MimeType extends StringType
{

    public function getSize(): int
    {
        return 127;
    }

}