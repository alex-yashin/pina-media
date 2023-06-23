<?php


namespace PinaMedia;


class Storages
{

    public static function get($storageKey)
    {
        static $storages = array();

        if (!empty($storages[$storageKey])) {
            return $storages[$storageKey];
        }

        return $storages[$storageKey] = new Storage($storageKey);
    }

}