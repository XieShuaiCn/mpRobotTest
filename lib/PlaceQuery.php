<?php

require_once("HttpClient.php");

abstract class PlaceQuery
{
    // 一度变化, 北京  85.567KM http://zhidao.baidu.com/question/2153234.html
    const SCALE = 85.567;

    protected $limit = 50;
    protected $range = PLACE_QUERY_RANGE;
    private $tableName = "PlaceCache";
      
       
    protected abstract function calBounds($lat, $lng);
    
    protected abstract function getApiType();


    protected function clearCache($lat, $lng, $range, $query)
    {
        $apiType = $this->getApiType();
        $sqlStr = "DELETE FROM {$this->tableName} WHERE ApiType='{$apiType}'
            AND Lat='{$lat}' AND Lng='{$lng}' AND Range='{$range}' AND QueryStr='{$query}'";
        return DBAct::execute($sqlStr);
    }
    
    
    protected function getCache($lat, $lng, $range, $query)
    {
        $apiType = $this->getApiType();
        $query = DBAct::escapeString($query);
        $sqlStr = "SELECT * FROM {$this->tableName} WHERE ApiType='{$apiType}'
            AND Lat='{$lat}' AND Lng='{$lng}' AND Range='{$range}' AND QueryStr='{$query}'
            ORDER BY ID DESC LIMIT 1";
        $result = DBAct::getOne($sqlStr);
        if ($result)
        {
            return unserialize($result["ResultsList"]);
        }
        return null;
    }
    
    
    protected function getHttpResponse($host, $uri)
    {
        $client = new HttpClient($host);
        $content = null;
        for ($i=0; $i<API_TRY_TIMES; $i++)
        {
            try
            {
                $content = $client->getUploadString($uri);
                break;
            }
            catch (Exception $e)
            {
                
            }
        }
        unset($client);
        return $content;
    }
    
    
    protected function poi2City($lat, $lng)
    {
        // http://api.map.baidu.com/geocoder?location=纬度,经度&output=输出格式类型&key=用户密钥
        $uri = "/geocoder?output=json&location={$lat},{$lng}&key=" . BAIDU_API_KEY;
        $resultStr = $this->getHttpResponse("api.map.baidu.com", $uri);
        $resultJson = json_decode($resultStr, true);
        if ($resultJson && $resultJson["status"] == "OK")
        {
            return $resultJson["result"]["addressComponent"]["city"];
        }
        return "";
    }
    
    
    public function getInverseKey($lat, $lng, $query)
    {
        $str = $lat . "_" . $lng . "_" . $query . "_" . $this->range;
        $code = md5($str);
        return $code;
    }
    
    
    /**
     * @return array[host, uri, url]
     */
    protected abstract function getQueryUriData($lat, $lng, $query);
    
    
    public function getDistance($lat1, $lng1, $lat2, $lng2)
    {
        $d = sqrt(pow(($lat1 - $lat2), 2) + pow(($lng1 - $lng2), 2));
        $d = $d * self::SCALE * 1000;
        return floor($d);
    }


    protected function addApiDataCache($lat, $lng, $query, array $data, $label="")
    {
        $apiType = $this->getApiType();
        $this->clearCache($lat, $lng, $this->range, $query);
        $dataSec = DBAct::escapeString(serialize($data));
        $uriData = $this->getQueryUriData($lat, $lng, $query);
        $nowStr = date("Y-m-d H:i:s");
        $inverseKey = $this->getInverseKey($lat, $lng, $query);
        $label = DBAct::escapeString($label);
        $sqlStr = "INSERT INTO {$this->tableName}(Lat, Lng, Range, QueryStr, ApiType, RequestURL, ResultsList, CreateTime, InverseKey, LabelInfo)
                        VALUES('{$lat}', '{$lng}', '{$this->range}', '{$query}', '{$apiType}', '{$uriData["url"]}', '{$dataSec}', '{$nowStr}', '{$inverseKey}', '{$label}')";
        return DBAct::execute($sqlStr);
    }
        
    
    /**
     *  "name" => 名字,
        "phone" => 电话,
        "address" => 地址,
        "zip" => 邮编,
        "lat" => 纬度,
        "lng" => 经度,
        "distance" => 当前距离,
     */
    protected abstract function getApiData($lat, $lng, $query);
    
    
    protected function sortByDistance(array &$data)
    {
        for ($i=0; $i<count($data) - 1; $i++)
        {
            for ($j=$i; $j<count($data); $j++)
            {
                if ($data[$i]["distance"] > $data[$j]["distance"])
                {
                    $temp = $data[$i];
                    $data[$i] = $data[$j];
                    $data[$j] = $temp;
                }
            }
        }
    }

    
    public function getResults($lat, $lng, $query="", $label="")
    {
        $results = $this->getCache($lat, $lng, $this->range, $query);
        if (!$results)
        {
            $results = $this->getApiData($lat, $lng, $query);
            $this->sortByDistance($results);
            
            // save cache
            $this->clearCache($lat, $lng, $this->range, $query);
            $this->addApiDataCache($lat, $lng, $query, $results, $label);
        }
        return $results;
    }
}
