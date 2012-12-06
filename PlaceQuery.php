<?php

require_once("HttpClient.php");

class PlaceQuery
{
    private static $obj = null;
    private $queryRange = PLACE_QUERY_RANGE;
    private $httpClient = null;
    private $cachePath = PLACE_CACHE_PATH;
    private $placeKey = BAIDU_API_KEY;
    

    private function __construct()
    {
        $this->httpClient = new HttpClient("api.map.baidu.com");
    }
    
    
    public static function getSigleton()
    {
        if (!self::$obj)
        {
            self::$obj = new PlaceQuery();
        }
        return self::$obj;
    }
    
    
    private function calBounds($lat, $lng)
    {
        // 一度变化, 北京  85.567KM http://zhidao.baidu.com/question/2153234.html
        $parameter = 85.567 * 1000;
        $differ = $this->queryRange / $parameter;
        $resultArr = array(
            $lat - $differ,
            $lng - $differ,
            $lat + $differ,
            $lng + $differ,
        );
        return implode(",", $resultArr);
    }
    
    
    private function getFileName($lat, $lng, $query)
    {
        $fileName = sha1($this->calBounds($lat, $lng) . $query);
        return $this->cachePath . DIRECTORY_SEPARATOR . $fileName;
    }
    
    
    private function getCache($lat, $lng, $query)
    {
        $fileName = $this->getFileName($lat, $lng, $query);
        if (file_exists($fileName))
        {
            $saveData = unserialize(file_get_contents($fileName));
            return $saveData["LIST"];
        }
        return null;
    }
    
    
    public function getResults($lat, $lng, $query="")
    {
        $results = $this->getCache($lat, $lng, $query);
        if ($results === null)
        {
            // http://api.map.baidu.com/place/search?&query=关键字&bounds=查询区域&output=输出格式类型&key=用户密钥     : lat,lng(左下角坐标),lat,lng(右上角坐标)
            // 计算范围
            $bounds = $this->calBounds($lat, $lng);
            $varArr = array(
                "query" => $query,
                "bounds" => $bounds,
                "output" => "json",
                "key" => $this->placeKey,
            );
            $resultTxt = $this->httpClient->getUploadString("/place/search", $varArr);
            $resultObj = json_decode($resultTxt, true);
            if (isset($resultObj["results"]) && is_array($resultObj["results"]))
            {
                $results = $resultObj["results"];
            }
            else
            {
                $results = array();
            }
            $saveData = array(
                "lat" => $lat,
                "lng" => $lng,
                "bounds" => $bounds,
                "query" => $query,
                "LIST" => $results,
                "responseText" => $resultTxt,
            );
            file_put_contents($this->getFileName($lat, $lng, $query), serialize($saveData));
        }
        return $results;
    }
}
