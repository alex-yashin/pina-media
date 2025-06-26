<?php

namespace PinaMedia;

use Pina\Data\Schema;
use Pina\Types\StringType;
use PinaMedia\Types\MimeType;
use PinaMedia\Types\StorageType;
use Pina\TableDataGateway;
use Pina\Types\IntegerType;
use Pina\Types\LongStringType;
use Pina\Types\TokenType;

use function Pina\__;

class MediaGateway extends TableDataGateway
{

    protected static $table = "media";

    /**
     * @return Schema
     * @throws \Exception
     */
    public function getSchema()
    {
        $schema = parent::getSchema();
        $schema->addAutoincrementPrimaryKey();
        $schema->add('storage', __('Хранилище'), StorageType::class);
        $schema->add('path', __('Путь'), LongStringType::class);
        $schema->add('hash', __('Хэш'), TokenType::class);
        $schema->add('title', __('Наименование'), StringType::class);
        $schema->add('width', __('Ширина'), IntegerType::class);
        $schema->add('height', __('Высота'), IntegerType::class);
        $schema->add('type', __('Mime-тип'), MimeType::class);
        $schema->add('size', __('Размер'), IntegerType::class);
        $schema->addUniqueKey(['storage', 'path']);
        return $schema;
    }

    /**
     * @param string $storageAlias
     * @param string $pathAlias
     * @return array
     * @throws \Exception
     */
    public function getWithUrl($storageAlias = 'storage', $pathAlias = 'path')
    {
        $data = $this->get();
        foreach ($data as $k => $v) {
            $data[$k]['url'] = Media::getUrl($v[$storageAlias], $v[$pathAlias]);
        }
        return $data;
    }

}