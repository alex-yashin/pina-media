<?php

namespace PinaMedia\Types;

use PinaMedia\Controls\MediaGallery;
use PinaMedia\Controls\MediaGalleryView;
use PinaMedia\MediaGateway;
use Pina\App;
use Pina\Controls\FormContentControl;
use Pina\Controls\FormControl;
use Pina\Data\Field;
use Pina\TableDataGateway;
use Pina\Types\TypeInterface;
use Pina\Types\ValidateException;

use function Pina\__;

class GalleryType implements TypeInterface
{
    /**
     * @var TableDataGateway
     */
    protected $relationTable;
    /**
     * @var string
     */
    protected $relationField = '';

    public function __construct(TableDataGateway $relationTable, $relationField)
    {
        $this->relationTable = $relationTable;
        $this->relationField = $relationField;
    }

    protected function makeDirectoryQuery(): TableDataGateway
    {
        return MediaGateway::instance();
    }

    protected function makeRelationQuery(): TableDataGateway
    {
        return clone $this->relationTable;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getVariants()
    {
        return $this->makeDirectoryQuery()->selectId()->selectTitle()->get();
    }

    public function setContext($context)
    {
        return $this;
    }

    public function makeControl(Field $field, $value): FormControl
    {
        $gallery = App::make(MediaGallery::class);

        /** @var MediaGallery $gallery */
        $gallery->setName($field->getName());
        $gallery->setMedia($value);

        /** @var FormContentControl $control */
        $control = App::make(FormContentControl::class);
        $star = $field->isMandatory() ? ' *' : '';
        $control->setTitle($field->getTitle() . $star);
        $control->setName($field->getName());
        $control->setContent($gallery);
        return $control;
    }

    public function format($value): string
    {
        return implode("\n", array_column($value, 'url'));
    }

    public function draw($value): string
    {
        /** @var MediaGalleryView $gallery */
        $gallery = App::make(MediaGalleryView::class);
        $gallery->setUrls(array_column($value, 'url'));
        return $gallery->drawWithWrappers();
    }

    public function play($value): string
    {
        return $this->draw($value);
    }

    public function getSize(): int
    {
        return 0;
    }

    public function getDefault()
    {
        return null;
    }

    public function isNullable(): bool
    {
        return true;
    }

    public function isSearchable(): bool
    {
        return false;
    }

    public function isFiltrable(): bool
    {
        return false;
    }

    public function normalize($value, $isMandatory)
    {
        if (!empty($value) && !is_array($value)) {
            throw new ValidateException(__("Загрузите корректные данные"));
        }

        return $value;
    }

    public function getData($id)
    {
        $relationQuery = $this->makeRelationQuery();
        $medias = $this->makeDirectoryQuery()
            ->select('*')
            ->innerJoin(
                $relationQuery
                    ->on('media_id', 'id')
                    ->onBy($this->relationField, $id)
            )
            ->orderBy($relationQuery->getAlias().'.order', 'asc')
            ->getWithUrl();

        return $medias;
    }

    public function setData($id, $value)
    {
        $this->makeRelationQuery()->whereBy($this->relationField, $id)->delete();

        $order = 0;
        if (!is_array($value)) {
            return;
        }
        foreach ($value as $mediaId) {
            $this->makeRelationQuery()->insertIgnore(
                [$this->relationField => $id, 'media_id' => $mediaId, 'order' => $order]
            );
            $order++;
        }
    }

    public function getSQLType(): string
    {
        return '';
    }

    public function filter(TableDataGateway $query, $key, $value): void
    {
        //фильтрацию не поддерживаем
    }
}