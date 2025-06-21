<?php

namespace PinaMedia\Types;

use Pina\Controls\HiddenInput;
use Pina\Controls\NoInput;
use PinaMedia\Controls\MediaGallery;
use PinaMedia\Controls\MediaGalleryView;
use PinaMedia\MediaGateway;
use Pina\App;
use Pina\Controls\FormControl;
use Pina\Data\Field;
use Pina\TableDataGateway;
use Pina\Types\TypeInterface;
use Pina\Types\ValidateException;

use function Pina\__;

class GalleryRelation implements TypeInterface
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

        $input = $this->resolveInput($field);

        $input->setName($field->getName());
        $input->setValue($value);
        $input->setTitle($field->getTitle());
        $input->setDescription($field->getDescription());
        $input->setRequired($field->isMandatory());

        return $input;
    }

    protected function resolveInput(Field $field): FormControl
    {
        if ($field->isStatic() && $field->isHidden()) {
            return $this->makeNoInput();
        }

        if ($field->isStatic()) {
            return $this->makeStatic();
        }

        if ($field->isHidden()) {
            return $this->makeHidden();
        }

        return $this->makeInput();
    }

    public function format($value): string
    {
        return implode("\n", array_column($value, 'url'));
    }

    public function draw($value): string
    {
        /** @var MediaGalleryView $gallery */
        $gallery = App::make(MediaGalleryView::class);
        $gallery->setValue($value);
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

    /**
     * @return MediaGallery
     */
    protected function makeInput()
    {
        return App::make(MediaGallery::class);
    }

    /**
     * @return MediaGalleryView
     */
    protected function makeStatic()
    {
        return App::make(MediaGalleryView::class);
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