<?php

namespace PinaMedia\Types;

use Pina\Controls\HiddenInput;
use Pina\Controls\NoInput;
use PinaMedia\Controls\MediaView;
use PinaMedia\Controls\MediaControl;
use PinaMedia\Media;
use PinaMedia\MediaGateway;
use Pina\Controls\FormControl;
use Pina\Types\IntegerType;
use Pina\App;
use Pina\Data\Field;
use Pina\Controls\Control;
use Pina\Types\ValidateException;

use function Pina\__;


class MediaType extends IntegerType
{

    /**
     * @param Field $field
     * @param mixed $value
     * @return Control
     * @throws \Exception
     */
    public function makeControl(Field $field, $value): FormControl
    {
        $input = $this->resolveInput($field);

        $input->setName($field->getName());
        $input->setValue($this->getMedia($value));
        $input->setTitle($field->getTitle());
        $input->setDescription($field->getDescription());
        $input->setRequired($field->isMandatory());

        return $input;
    }

    /**
     * @param mixed $value
     * @return mixed|string
     * @throws \Exception
     */
    public function format($value): string
    {
        $media = $this->getMedia($value);
        return $media['url'] ?? '';
    }

    /**
     * @param mixed $value
     * @return string
     * @throws \Exception
     */
    public function draw($value): string
    {
        $view = App::make(MediaView::class);
        $view->setValue($this->getMedia($value));
        return $view;
    }

    /**
     * @param $id
     * @return array
     * @throws \Exception
     */
    protected function getMedia($id)
    {
        $media = MediaGateway::instance()
            ->select('id')
            ->select('type')
            ->select('storage')
            ->select('path')
            ->find($id);

        if (empty($media)) {
            return ['id' => 0, 'type' => '', 'url' => ''];
        }
        $media['url'] = Media::getUrl($media['storage'], $media['path']);
        unset($media['storage']);
        unset($media['path']);
        return $media;
    }

    public function isNullable(): bool
    {
        return true;
    }

    public function getDefault()
    {
        return null;
    }

    public function normalize($value, $isMandatory)
    {
        if (empty($value) && $isMandatory) {
            throw new ValidateException(__("Укажите значение"));
        }

        if (empty($value) && $this->isNullable()) {
            return null;
        }

        $value = intval($value);

        return parent::normalize($value, $isMandatory);
    }

    /**
     * @return MediaControl
     */
    protected function makeInput()
    {
        return App::make(MediaControl::class);
    }

    /**
     * @return MediaView
     */
    protected function makeStatic()
    {
        return App::make(MediaView::class);
    }

    /**
     * @return HiddenInput
     */
    protected function makeHidden()
    {
        return App::make(HiddenInput::class);
    }

    protected function makeNoInput()
    {
        return App::make(NoInput::class);
    }

}