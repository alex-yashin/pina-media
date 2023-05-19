<?php

namespace PinaMedia;

use Pina\Log;

class ImageResizer
{

    private $width;
    private $height;
    private $crop;
    private $trim;
    private $mime;

    public function __construct($width = 0, $height = 0, $crop = false, $trim = false)
    {
        $this->width = $width;
        $this->height = $height;
        $this->crop = $crop;
        $this->trim = $trim;
    }

    public function resize($source, $target = null)
    {
        $imageData = $this->getImageData($source);
        if (empty($imageData)) {
            return array(0, 0);
        }

        $imageSource = $imageData['source'];
        $sourceWidth = $imageData['width'];
        $sourceHeight = $imageData['height'];
        $imageFormat = $imageData['format'];

        list($sourceWidth, $sourceHeight, $sourceLeft, $sourceTop) = $this->trim($imageSource, $sourceWidth, $sourceHeight);
        list($targetWidth, $targetHeight, $targetLeft, $targetTop) = $this->calc($sourceWidth, $sourceHeight);

        $imageTarget = $this->prepareImageTarget();
        if (!imagecopyresampled($imageTarget, $imageSource, $targetLeft, $targetTop, $sourceLeft, $sourceTop, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight)) {
            @imagedestroy($imageSource);
            @imagedestroy($imageTarget);
            return array(0, 0);
        }

        if (empty($target)) {
            header('Content-Type: image/' . $imageFormat);
            header('Content-Disposition: inline; filename="' . basename($source) . '"');
            $this->outCacheHeaders(315360000);
            $imageCreateFunction = 'image' . $imageFormat;
            if (!$imageCreateFunction($imageTarget)) {
                @imagedestroy($imageSource);
                @imagedestroy($imageTarget);
                return array(0, 0);
            }
        }

        if (!$this->createImage($imageTarget, $target, $imageFormat)) {
            @imagedestroy($imageSource);
            @imagedestroy($imageTarget);
            return array(0, 0);
        }


        @imagedestroy($imageSource);
        @imagedestroy($imageTarget);
        return array($targetWidth, $targetHeight);
    }

    public function outCacheHeaders($secondsToCache)
    {
        header('Cache-control: public');
        $lastModified = time();
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        header('Expires: '.gmdate('D, d M Y H:i:s', $lastModified + $secondsToCache) . ' GMT');
    }

    private function createImage($data, $filePath, $imageFormat)
    {
        if (!in_array($imageFormat, array('png', 'jpeg', 'gif'))) {
            return false;
        }

        $imageCreateFunction = 'image' . $imageFormat;
        if (!$imageCreateFunction($data, $filePath)) {
            return false;
        }

        return true;
    }

    public function crop($sourcePath, $targetPath, $rect)
    {
        $imageData = $this->getImageData($sourcePath);
        if (empty($imageData)) {
            return false;
        }

        $croppedImage = imagecrop($imageData['source'], $rect);
        if ($croppedImage === FALSE) {
            @imagedestroy($imageData['source']);
            return false;
        }

        if (!$this->createImage($croppedImage, $targetPath, $imageData['format'])) {
            @imagedestroy($imageData['source']);
            @imagedestroy($croppedImage);
            return false;
        }

        @imagedestroy($imageData['source']);
        @imagedestroy($croppedImage);

        return true;
    }

    /**
     * @param $sourcePatch
     * @return array
     */
    private function getImageData($sourcePatch)
    {
        if (!$this->width && !$this->height) {
            return [];
        }

        $sourceImageData = $this->getSourceData($sourcePatch);
        if ($sourceImageData === false) {
            return [];
        }

        $this->mime = $sourceImageData['mime'];
        $imageFormat = $this->getImageFormat($sourceImageData['mime']);
        if ($imageFormat === false) {
            return [];
        }

        $imageCreateFunction = 'imagecreatefrom' . $imageFormat;
        if (!function_exists($imageCreateFunction)) {
            Log::error("image.resize", "function ".$imageCreateFunction." does not exists");
            return [];
        }

        list($sourceWidth, $sourceHeight) = $sourceImageData;
        if ($sourceWidth > 6000 || $sourceHeight > 6000) {
            return [];
        }

        $imageSource = @$imageCreateFunction($sourcePatch);
        if ($imageSource === false) {
            return [];
        }

        $imageSource = $this->rotateByOrientation($imageSource, $sourcePatch);

        return [
            'source' => $imageSource,
            'width' => imagesx($imageSource),
            'height' => imagesy($imageSource),
            'format' => $imageFormat,
        ];
    }

    private function getSourceData($source)
    {
        if (!is_file($source)) {
            return false;
        }
        return getimagesize($source);
    }

    private function getImageFormat($mime)
    {
        $format = strtolower(substr($mime, strpos($mime, '/') + 1));
        return in_array($format, array('png', 'jpeg', 'gif')) ? $format : false;
    }

    private function rotateByOrientation($imageSource, $sourcePatch)
    {
        $exif = @exif_read_data($sourcePatch);//поддерживаются только jpeg/tiff, а по png сыпет warning`и
        if ($imageSource && $exif && isset($exif['Orientation']))
        {
            $orientation = $exif['Orientation'];

            if ($orientation == 6 || $orientation == 5)
                $imageSource = imagerotate($imageSource, 270, null);
            if ($orientation == 3 || $orientation == 4)
                $imageSource = imagerotate($imageSource, 180, null);
            if ($orientation == 8 || $orientation == 7)
                $imageSource = imagerotate($imageSource, 90, null);

            if ($orientation == 5 || $orientation == 4 || $orientation == 7)
                imageflip($imageSource, IMG_FLIP_HORIZONTAL);
        }

        return $imageSource;
    }

    private function prepareImageTarget()
    {
        $imageTarget = imagecreatetruecolor($this->width, $this->height);
        imagesavealpha($imageTarget, true);
        imagealphablending($imageTarget, false);
        imagefill($imageTarget, 0, 0, 0x7fffffff);
        return $imageTarget;
    }

    private function trim($img, $sourceWidth, $sourceHeight)
    {
        if (empty($this->trim) || $this->trim < 0) {
            return [$sourceWidth, $sourceHeight, 0, 0];
        }
        $colors = $this->getTrimColors($img, $this->trim - 1);

        $top = 0;
        for (; $top < $sourceHeight; ++$top) {
            for ($x = 0; $x < $sourceWidth; ++$x) {
                $currentColor = imagecolorat($img, $x, $top);
                if (!in_array($currentColor, $colors)) {
                    break 2;
                }
            }
        }

        $bottom = $sourceHeight - 1;
        for (; $bottom >= 0; $bottom--) {
            for ($x = 0; $x < $sourceWidth; $x++) {
                $currentColor = imagecolorat($img, $x, $bottom);
                if (!in_array($currentColor, $colors)) {
                    break 2;
                }
            }
        }

        $left = 0;
        for (; $left < $sourceWidth; ++$left) {
            for ($y = 0; $y < $sourceHeight; ++$y) {
                $currentColor = imagecolorat($img, $left, $y);
                if (!in_array($currentColor, $colors)) {
                    break 2;
                }
            }
        }

        $right = $sourceWidth - 1;
        for (; $right >= 0; $right--) {
            for ($y = 0; $y < $sourceHeight; ++$y) {
                $currentColor = imagecolorat($img, $right, $y);
                if (!in_array($currentColor, $colors)) {
                    break 2;
                }
            }
        }
        
        return [$right - $left, $bottom - $top, $left, $top];
    }
    
    protected function getTrimColors($img, $diff = 0)
    {
        $color = imagecolorat($img, 0, 0);
        $colorRgb = imagecolorsforindex($img, $color);

        $colors = [$color];
        for ($i = 1; $i < $diff; $i++) {
            $colors [] = imagecolorexact($img, max(0, $colorRgb['red'] - $i), max(0, $colorRgb['green'] - $i), max(0, $colorRgb['blue'] - $i));
            $colors [] = imagecolorexact($img, min(255, $colorRgb['red'] + $i), min(255, $colorRgb['green'] + $i), min(255, $colorRgb['blue'] + $i));
        }
        return array_unique($colors);
    }

    public function calc($sourceWidth, $sourceHeight)
    {
        if (empty($sourceWidth) || empty($sourceHeight)) {
            return array(0, 0, 0, 0);
        }

        $xRatio = $this->width / $sourceWidth;
        $yRatio = $this->height / $sourceHeight;
        if (!$this->height) {
            $yRatio = $xRatio;
            $this->height = floor($yRatio * $sourceHeight);
        } elseif (!$this->width) {
            $xRatio = $yRatio;
            $this->width = floor($xRatio * $sourceWidth);
        }
        $ratio = $this->crop ? max($xRatio, $yRatio) : min($xRatio, $yRatio);
        $ratioByX = $xRatio === $ratio;
        $targetWidth = $ratioByX ? $this->width : floor($sourceWidth * $ratio);
        $targetHeight = $ratioByX ? floor($sourceHeight * $ratio) : $this->height;
        $targetLeft = $ratioByX ? 0 : floor(($this->width - $targetWidth) / 2);
        $targetTop = $ratioByX ? floor(($this->height - $targetHeight) / 2) : 0;
        return array($targetWidth, $targetHeight, $targetLeft, $targetTop);
    }

    public function getSize()
    {
        return array($this->width, $this->height);
    }
    
    public function getMime()
    {
        return $this->mime;
    }

}
