<?php
require_once("DBAct.php");

class MapResult
{
    private static $tableName = "PlaceCache";
    
    private static function getData($key)
    {
        $sqlStr = "SELECT * FROM " . self::$tableName . " WHERE InverseKey='{$key}' LIMIT 1";
        return DBAct::getOne($sqlStr);
    }
    
    
    private static function formatNum($n)
    {
        return number_format($n, 8, ".", "");
    }
    
        
    public static function getImgUrl($llat, $llng, $tlat, $tlng)
    {
        // http://sobar.soso.com/t/82751256
        $llat = self::formatNum($llat);
        $llng = self::formatNum($llng);
        $tlat = self::formatNum($tlat);
        $tlng = self::formatNum($tlng);
        $imgUrl = "http://st.map.qq.com/staticmap?size=480*480&center={$llng},{$llat}&zoom=15&markers={$llng},{$llat},green|{$tlng},{$tlat},red";
        return $imgUrl;
    }
    
    
    public static function getResultData($key)
    {
        $data = self::getData($key);
        if ($data)
        {
            $resultList = unserialize($data["ResultsList"]);
            $data["ResultsListData"] = $resultList;
        }
        return $data;
    }
    
    
    public static function getMakeDownStr($key)
    {
        $data = self::getData($key);
        $text = "";
        if ($data)
        {
            $textArr = array();
            $lat = $data["Lat"];
            $lng = $data["Lng"];
            $queryStr = $data["QueryStr"];
            $textArr[] = "# {$queryStr}";
            $textArr[] = "* * *";
            $resultList = unserialize($data["ResultsList"]);
            for ($i=0; $i<count($resultList); $i++)
            {
                $textArr[] = "### [{$resultList[$i]["name"]}](javascript:alert(1);)";
                $textArr[] = "##### {$resultList[$i]["address"]}    ({$resultList[$i]["distance"]}m)";
                $textArr[] = "![{$resultList[$i]["name"]}](" . self::getImgUrl($lat, $lng, $resultList[$i]["lat"], $resultList[$i]["lng"]) . ")";
            }
            $text =  implode("\r\n", $textArr);
        }
        return $text;
    }
}
