<?php
require_once("PlaceQuery.php");

class SoSoPlaceQuery extends PlaceQuery
{    
    private $cbName = "cbhaeminru";
    
    protected function getApiType()
    {
        return "SOSO";
    }
    
    
    protected function calBounds($lat, $lng)
    {
        $parameter = self::SCALE * 1000;
        $differ = $this->range / $parameter;
        $resultArr = array(
            $lng + $differ,
            $lat + $differ,
            $lng - $differ,
            $lat - $differ,
        );
        return implode(",", $resultArr);
    }

       
    protected function getQueryUriData($lat, $lng, $query)
    {
        // http://api.map.qq.com/?b=查询区域&l=11&wd=[查询关键词]&pn=0&rn=99&c=[地区中心]&qt=poi&output=jsonp&fr=mapapi&cb=cbhaeminru&t=[当前毫秒]
        $bounds = $this->calBounds($lat, $lng);
        $queryCode = urlencode($query);
        $time = floor(microtime(true));
        $cityCode = urlencode($this->poi2City($lat, $lng));
        $uri = "/?b={$bounds}&l=11&wd={$queryCode}&pn=0&rn={$this->limit}&c={$cityCode}&qt=poi&output=jsonp&fr=mapapi&cb={$this->cbName}&t={$time}";
        return array(
            "host" => "api.map.qq.com",
            "uri" => $uri,
            "url" => "http://api.map.qq.com{$uri}",
        );
    }
        
    
    protected function getApiData($lat, $lng, $query)
    {
        $uriData = $this->getQueryUriData($lat, $lng, $query);
        $resultStr = $this->getHttpResponse($uriData["host"], $uriData["uri"]);
        $filteReg = "/{$this->cbName}\s*&&\s*{$this->cbName}\s*\(([\s\S]*)\)\s*$/";
        $resultStr = preg_replace($filteReg, "$1", $resultStr);
        // GBK -> UTF-8
        $resultStr = mb_convert_encoding($resultStr, "UTF-8", "GBK");
        $resultJson = json_decode($resultStr, true);
        $retArr = array();
        if ($resultJson && isset($resultJson["detail"]) && isset($resultJson["detail"]["pois"]))
        {
            foreach ($resultJson["detail"]["pois"] as $data)
            {
                $distance = $this->getDistance($lat, $lng, $data["pointy"], $data["pointx"]);
                // 排除 soso 搜索的错误
                $lineRange = (int)sqrt(2 * pow(PLACE_QUERY_RANGE, 2));
                //if ($distance <= $lineRange)
                //{
                    $retArr[] = array(
                        "name" => $data["name"],
                        "phone" => $data["phone"],
                        "address" => $data["addr"],
                        "zip" => $data["zip"],
                        "lat" => $data["pointy"],
                        "lng" => $data["pointx"],
                        "distance" => $this->getDistance($lat, $lng, $data["pointy"], $data["pointx"]),
                    );
                //}
            }
        }
        return $retArr;
    }
}
