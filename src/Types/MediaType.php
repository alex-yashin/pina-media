<?php

namespace PinaMedia\Types;

use PinaMedia\Controls\MediaView;
use Pina\Controls\FormContentControl;
use PinaMedia\Controls\MediaControl;
use PinaMedia\Media;
use PinaMedia\MediaGateway;
use Pina\Controls\FormControl;
use Pina\ResourceManagerInterface;
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
        /** @var MediaControl $input */
        $input = App::make(MediaControl::class);
        $input->setName($field->getKey());
        $input->setMedia($this->getMedia($value));

        /** @var FormContentControl $control */
        $control = App::make(FormContentControl::class);
        $star = $field->isMandatory() ? ' *' : '';
        $control->setTitle($field->getTitle() . $star);
        $control->setName($field->getKey());
        $control->setContent($input);
        return $control;
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
        $media = $this->getMedia($value);
        if (empty($media['url'])) {
            return '';
        }

        $view = App::make(MediaView::class);
        $view->setUrl($media['url']);
        return $view;
    }

    /**
     * @param $id
     * @return array
     * @throws \Exception
     */
    private function getMedia($id)
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

    protected function resources(): ResourceManagerInterface
    {
        return App::container()->get(ResourceManagerInterface::class);
    }

    public function isNullable(): bool
    {
        return true;
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

}
