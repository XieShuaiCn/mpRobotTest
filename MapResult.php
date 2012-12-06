<?php

class MapResult
{
    private static $cachePath = PLACE_CACHE_PATH;
    
    private static function getFileName($key)
    {
        return self::$cachePath . DIRECTORY_SEPARATOR . $key;
    }
    
    
    private static function getData($key)
    {
        $fileName = self::getFileName($key);
        if (file_exists($fileName))
        {
            return unserialize(file_get_contents($fileName));
        }
        return null;
    }
    
    
    private static function formatNum($n)
    {
        return number_format($n, 8, ".", "");
    }
    
    
    private static function getImgUrl($llat, $llng, $tlat, $tlng)
    {
        $llat = self::formatNum($llat);
        $llng = self::formatNum($llng);
        $tlat = self::formatNum($tlat);
        $tlng = self::formatNum($tlng);
        $imgUrl = "http://api.map.baidu.com/staticimage?center={$llng},{$llat}&width=400&height=400&zoom=14";
        $imgUrl .= "&markers={$llng},{$llat}|{$tlng},{$tlat}&markerStyles=m,,#0000FF|m,,#FF0000";
        return $imgUrl;
    }
    
    
    public static function getMakeDownStr($key)
    {
        $data = self::getData($key);
        $text = "";
        if ($data)
        {
            $textArr = array();
            $lat = $data["lat"];
            $lng = $data["lng"];
            $queryStr = $data["query"];
            $textArr[] = "### " . $queryStr;
            $textArr[] = "    ";
            $textArr[] = "    ";
            $textArr[] = "    ";
            $textArr[] = "    ";
            $textArr[] = "    ";
            for ($i=0; $i<count($data["LIST"]); $i++)
            {
                $textArr[] = "#### " . $data["LIST"][$i]["name"];
                $textArr[] = "![{$data["LIST"][$i]["name"]}](" . self::getImgUrl($lat, $lng, $data["LIST"][$i]["location"]["lat"], $data["LIST"][$i]["location"]["lng"]) . ")";
                $textArr[] = "    ";
                $textArr[] = "    ";
                $textArr[] = "    ";
            }
            $text =  implode("\r\n", $textArr);
        }
        return $text;
    }
}
