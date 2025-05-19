<?php

namespace PinaMedia\Controls;

use Pina\Controls\FormControl;

abstract class MediaStaticFormControl extends FormControl
{

    protected $value;

    public function setTitle(string $title): FormControl
    {
        return $this;
    }

    public function setName(string $name): FormControl
    {
        return $this;
    }

    public function setValue($value): FormControl
    {
        $this->value = $value;
        return $this;
    }

    public function setDescription(string $description): FormControl
    {
        return $this;
    }

    public function setRequired(bool $required = true): FormControl
    {
        return $this;
    }

    public function setCompact(bool $compact = true): FormControl
    {
        return $this;
    }
}