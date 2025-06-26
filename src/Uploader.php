<?php

namespace PinaMedia;

use Exception;
use Pina\BadRequestException;
use Pina\Log;
use RuntimeException;

use function Pina\__;

class Uploader
{
    var $queue = [];

    protected $allowedMimeTypes = [
        'image/*',
    ];

    public function __construct()
    {

        foreach ($_FILES as $f) {
            if (is_array($f['tmp_name'])) {
                foreach ($f['tmp_name'] as $index => $tmp) {
                    $line = [];
                    foreach ($f as $key => $values) {
                        $line[$key] = $values[$index];
                    }
                    $this->queue[] = $line;
                }
            } else {
                $this->queue[] = $f;
            }
        }
    }

    public function allowMimeType($pattern)
    {
        $this->allowedMimeTypes[] = $pattern;
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function save(): array
    {
        $mediaIds = [];

        while ($mediaId = $this->saveNext()) {
            $mediaIds[] = $mediaId;
        }

        return $mediaIds;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function saveNext(): ?string
    {
        $file = $this->getNextUploadedFile();
        if (empty($file)) {
            return null;
        }

        $match = false;
        foreach ($this->allowedMimeTypes as $pattern) {
            if ($file->isMimeType($pattern)) {
                $match = true;
            }
        }

        if (!$match) {
            throw new BadRequestException(__('Неверный тип файла') . ': ' . $file->getMimeType());
        }

        $file->moveToStorage();
        $mediaId = $file->saveMeta();

        if (empty($mediaId)) {
            throw new Exception('Error saving meta');
        }

        return $mediaId;
    }

    public function getNextUploadedFile(): ?File
    {
        $next = array_shift($this->queue);
        if (empty($next)) {
            return null;
        }
        $tmpName = $next['tmp_name'];
        $fileName = $next['name'];
        $fileType = $next['type'];

        if (!is_uploaded_file($tmpName) || !file_exists($tmpName)) {
            Log::warning('media', "Невозможно загрузить файл ".$tmpName);
            throw new RuntimeException('Wrong image');
        }

        $name = $this->normalizeName(pathinfo($fileName, PATHINFO_FILENAME));
        $ext = $this->normalizeName(pathinfo($fileName, PATHINFO_EXTENSION));
        if (strlen($name) < 3) {
            $name = uniqid();
        }

        return new File($tmpName, $name . '.' . $ext, $fileType, $fileName);

    }

    protected function normalizeName($name)
    {
        return preg_replace('/[^\w^\-]/', '', $name);
    }


}