<?php

require_once("SoSoPlaceQuery.php");
require_once("UserCache.php");

class Reply
{
    private $myUserName = "";
    /**
     * @var PlaceQuery 
     */
    private $placeQuery = null;
    /**
     * @var UserCache
     */
    private $userCache = null;
    
    public function __construct()
    {
        $this->placeQuery = new SoSoPlaceQuery();
    }
    
    
    public function responseMsg($content)
    {
        $content = trim($content);
        $retText = "";
        if (!empty($content)) 
        {
            $reqObj = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
            $msgType = strtolower((string)$reqObj->MsgType);
            $this->myUserName = (string)$reqObj->ToUserName;
            $toUserName = (string)$reqObj->FromUserName;
            $this->userCache = new UserCache($toUserName);
            if ($msgType == "text")
            {
                return $this->replyTextType($reqObj, $toUserName);
            }
            else if ($msgType == "image")
            {
                return $this->replyImageType($reqObj, $toUserName);
            }
            else if ($msgType == "location")
            {
                return $this->replyLocationType($reqObj, $toUserName);
            }
            else
            {
                return $this->buildTextData($toUserName, "类型？ " . $msgType);
            }
        }
        
        return $retText;
    }
    
    
    private function getPlaceResult($lat, $lng, $query, $label)
    {
        // 实现逻辑
        $data = $this->placeQuery->getResults($lat, $lng, $query, $label);
        $iKey = $this->placeQuery->getInverseKey($lat, $lng, $query);
        $retTextArr = array();
        $retTextArr[] = "{$label} 搜索周边 {$query} 共找到有［" . count($data) . "］个结果";
        $retTextArr[] = "";
        for ($i=0; $i<count($data); $i++)
        {
            if ($i >= 12)
            {
                break;
            }

            $retTextArr[] = "  - {$data[$i]["name"]}  ({$data[$i]["distance"]}m)";
        }
        $retTextArr[] = "";
        if (count($data) > 0)
        {
            $retTextArr[] .= "  ** 列出的是直线距离, 现只能计算直线距离...";
            // link
            $retTextArr[] = "http://" . BASE_URL . "/mpRobotTest/MAP{$iKey}";
        }
        else
        {
            $retTextArr[] .= " 抱歉，喵认为您的关键词 {$query} 不行～ 完全不行啊！";
        }
        $retText = implode("\r\n", $retTextArr);
        return $retText;
    }
    
    
    private function replyImageType(SimpleXMLElement $reqObj, $toUserName)
    {
        $picUrl = (string)$reqObj->PicUrl;
        return $this->buildTextData($toUserName, "图片不能识别！！ {$picUrl}");
    }
    
    
    private function replyTextType(SimpleXMLElement $reqObj, $toUserName)
    {
        $msgContent = trim(strip_tags((string)$reqObj->Content));
        $outputContent = "";
        $exCludeReg = "/^Hello2BizUser$/i";
        if (empty($msgContent))
        {
            $outputContent = $this->buildTextData($toUserName, "抱歉，关键词为空，喵无法记录！");
        }
        else if (preg_match($exCludeReg, $msgContent))
        {
            $outputContent = null;
        }
        else
        {
            $lastPositionData = $this->userCache->getLastQueryPosition();
            $this->userCache->addNewQuery(UserCache::QUERY_TYPE_KEYWORD, $msgContent);
            if ($lastPositionData)
            {
                $lat = $lastPositionData["lat"];
                $lng = $lastPositionData["lng"];
                $label = $lastPositionData["label"];
                $outputContent = $this->getPlaceResult($lat, $lng, $msgContent, $label);
            }
            else
            {
                $outputContent = $this->buildTextData($toUserName, "喵已经记录了您的{$msgContent}查询，麻烦请通过给微信客户端给喵我您当前的地理位置，请使用输入框左边的“加号”，传送位置信息给喵哦…… 您的地理位置将有" . UserCache::QUERY_POSITION_EXP . "分钟的有效期……");
            }
        }
        if ($outputContent)
        {
            return $this->buildTextData($toUserName, $outputContent);
        }
        return null;
    }
    
    
    private function replyLocationType(SimpleXMLElement $reqObj, $toUserName)
    {
        $lat = (float)$reqObj->Location_X;
        $lng = (float)$reqObj->Location_Y;
        $label = (string)$reqObj->Label;
        //$scale = (int)$reqObj->Scale;       // 缩放大小
        $query = $this->userCache->getLastQueryKeyword();
        $this->userCache->addNewQuery(UserCache::QUERY_TYPE_POSITION, array(
                                                                            "lat" => $lat,
                                                                            "lng" => $lng,
                                                                            "label" => $label,
                                                                        ));
        $outputContent = "";
        if ($query)
        {
            $outputContent = $this->getPlaceResult($lat, $lng, $query, $label);
        }
        else
        {
            $outputContent = $this->buildTextData($toUserName, "喵明白您想查询{$label}周边的信息，但喵还不清楚您具体想查询啥？麻烦请告知喵您的关键词。的关键词喵将为您保持" . UserCache::QUERY_KEYWORD_EXP . "分钟的有效期哦～");
        }
        return $this->buildTextData($toUserName, $outputContent);
    }
    
    
    private function createTextNode(DOMDocument $dom, DOMNode $parentNode, $name, $content)
    {
        $theNode = $dom->createElement($name);
        $textNode = $dom->createCDATASection($content);
        $theNode->appendChild($textNode);
        $parentNode->appendChild($theNode);
    }
    
    
//    private function buildImageData()
//    {
//        $dom = new DOMDocument();
//        $root = $dom->createElement("xml");
//        $dom->appendChild($root);
//    }
    
    
    private function buildTextData($toUserName, $content)
    {
        $dom = new DOMDocument();
        $root = $dom->createElement("xml");
        $dom->appendChild($root);
        
        $this->createTextNode($dom, $root, "ToUserName", $toUserName);
        $this->createTextNode($dom, $root, "FromUserName", $this->myUserName);
        $this->createTextNode($dom, $root, "CreateTime", time());
        $this->createTextNode($dom, $root, "MsgType", "text");
        $this->createTextNode($dom, $root, "Content", $content);
        $this->createTextNode($dom, $root, "FuncFlag", "0");
        
        return $dom->saveXML();
    }

}