<?php

namespace PinaMedia;

class ImageTag
{

    private $params = array();

    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function render()
    {
        $image = $this->findImage();
        if (empty($image)) {
            return '';
        }

        $src = Media::getUrl($image['storage'], $image['path']);
        $resizeResource = Media::getStorageConfig($image['storage'], 'resize');

        if ($resizeResource && $this->isResizeRequired($image)) {
            $resizeBase = Media::getStorageConfig($resizeResource, 'url');
            if (empty($resizeBase)) {
                $parsed = parse_url($src);
                $resizeBase = (!empty($parsed['host']) ? (($parsed['scheme'] ?? 'http') . '://' . $parsed['host']) : '');
            }
            $resizeMode = $this->getResizeMode();
            $src = rtrim($resizeBase, '/') . '/' . $resizeResource . '/' . $resizeMode . '/' . Media::encodePath($image['path']);
        }

        if (empty($src)) {
            return '';
        }

        if (!empty($this->params['return']) && $this->params['return'] === 'src') {
            return $src;
        }

        if (!$resizeResource || !empty($this->params['show_dimensions'])) {
            $this->addSizesToStyle();
        }

        return $this->getTag($src);
    }

    private function getResizeMode()
    {
        $resizeMode = '';
        if (!empty($this->params['width'])) {
            $resizeMode .= 'w' . $this->params['width'];
        }
        if (!empty($this->params['height'])) {
            $resizeMode .= 'h' . $this->params['height'];
        }
        if (!empty($this->params['crop'])) {
            $resizeMode .= 'c' . $this->params['crop'];
        }
        if (!empty($this->params['trim'])) {
            $resizeMode .= 't' . $this->params['trim'];
        }
        return $resizeMode;
    }

    private function findImage()
    {
        if (!empty($this->params['media']) && !empty($this->params['media']['storage']) && !empty($this->params['media']['path'])) {
            return $this->params['media'];
        }

        if (!empty($this->params['media']) && !empty($this->params['media']['id'])) {
            return MediaGateway::instance()->find($this->params['media']['id']);
        }

        if (!empty($this->params['id'])) {
            return MediaGateway::instance()->find($this->params['id']);
        }
        /*
          if (!empty($this->params['storage']) && !empty($this->params['path'])) {
          return MediaGateway::instance()->whereBy('storage', $this->params['storage'])->whereBy('path', $this->params['path'])->first();
          }
         */
        return array();
    }

    private function addSizesToStyle()
    {
        if (!isset($this->params['style'])) {
            $this->params['style'] = '';
        } else {
            if (strpos($this->params['style'], 'width:') !== false || strpos(
                    $this->params['style'],
                    'height:'
                ) !== false) {
                return;
            }
        }
        foreach (array('width', 'height') as $p) {
            $v = empty($this->$p) ? (empty($this->params[$p]) ? '' : $this->params[$p]) : $this->$p;
            if ($v) {
                $this->params['style'] .= $p . ':' . $v . 'px;';
            }
        }
    }

    private function getTag($src)
    {
        $img = $this->getTagByParams($src, $this->params);
        if (!empty($this->params['lazy'])) {
            $img .= '<noscript>';
            $params = $this->params;
            unset($params['lazy']);
            $img .= $this->getTagByParams($src, $params);
            $img .= '</noscript>';
        }
        return $img;
    }

    private function getTagByParams($src, $params)
    {
        $img = '<img ' . (!empty($params['lazy']) ? $params['lazy'] : 'src') . '="' . $src . '"';
        foreach (array('alt', 'class', 'style', 'title') as $p) {
            $img .= empty($params[$p]) ? '' : ' ' . $p . '="' . $params[$p] . '"';
        }
        $img .= ' />';
        return $img;
    }

    private function isResizeRequired($image)
    {
        if (!empty($this->params['width']) && ($this->params['width'] != ($image['width'] ?? ''))) {
            return true;
        }

        if (!empty($this->params['height']) && ($this->params['height'] != ($image['height'] ?? ''))) {
            return true;
        }

        if (!empty($this->params['trim']) && (!empty($this->params['width']) || !empty($this->params['height']))) {
            return true;
        }

        return false;
    }

}
