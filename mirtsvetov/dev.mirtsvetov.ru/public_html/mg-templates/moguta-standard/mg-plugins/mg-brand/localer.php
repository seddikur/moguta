<?php

class Localer {
    static $tableName = "mg-brand";

    /**
     * Получает текст панели
     * @return string
     */
    public static function getText($id, $locale = "LANG")
    {
        if ($locale == "LANG") {
            $locale = LANG;
        }
        $sql = "SELECT * FROM `".PREFIX.self::$tableName."` WHERE `id` = ".DB::quoteInt($id);
        $array = DB::fetchAssoc(DB::query($sql));
        MG::loadLocaleData($id, $locale, self::$tableName, $array); 
        return $array; 
    }   

    public static function setText($text, $locale = "default")
    {
        if ($locale == "default") {
            $sql = "UPDATE `".PREFIX.self::$tableName."` SET `text` = ".DB::quote($text)." WHERE `id` = 1";
            $result = DB::query($sql);
            return $result;
        }

        $text = array('text' => $text);
        MG::saveLocaleData(1, $locale, self::$tableName, $text);
        return true;
    }
}