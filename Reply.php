<?php

require_once("PlaceQuery.php");
require_once("UserCache.php");

class Reply
{
    private $myUserName = "";
    /**
     * @var PlaceQuery 
     */
    private $placeQuery = null;
    
    public function __construct()
    {
        $this->placeQuery = PlaceQuery::getSigleton();
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
    
    
    private function replyImageType(SimpleXMLElement $reqObj, $toUserName)
    {
        $picUrl = (string)$reqObj->PicUrl;
        return $this->buildTextData($toUserName, "图片不能识别！！ {$picUrl}");
    }
    
    
    private function replyTextType(SimpleXMLElement $reqObj, $toUserName)
    {
        $msgContent = (string)$reqObj->Content;
        UserCache::simpleAddTalk($toUserName, $msgContent);
        return $this->buildTextData($toUserName, "您的查询关键词, 请输入您的位置, 获取相关查询.");
    }
    
    
    private function replyLocationType(SimpleXMLElement $reqObj, $toUserName, $query="")
    {
        $lat = (float)$reqObj->Location_X;
        $lng = (float)$reqObj->Location_Y;
        $label = (string)$reqObj->Label;
        $scale = (int)$reqObj->Scale;       // 缩放大小
        $query = UserCache::simpleGetLastTalk($toUserName);
        if ($query)
        {
            // 实现逻辑
            $results = $this->placeQuery->getResults($lat, $lng, $query);
            /**
                {
                     "name":"中国工商银行五四大街支行",
                     "location":{
                         "lat":39.930678,
                         "lng":116.409793
                     },
                     "address":"北京市东城区五四大街33号一层",
                     "telephone":"(010)64043201",
                     "uid":"026dcbfe3b02ce4a6c20b93c",
                     "tag":"银行,王府井/东单",
                     "detail_url":"http://api.map.baidu.com/place/detail?uid=026dcbfe3b02ce4a6c20b93c&output=html&source=placeapi"
                 },
             */
            $retTextArr = array();;
            for ($i=0; $i<count($results); $i++)
            {
                $retTextArr[] = $results[$i]["name"] . " : " . $results[$i]["address"] . "\r\n";
            }
            $retText = implode("\r\n", $retTextArr);
            return $this->buildTextData($toUserName, $retText);
        }
        else
        {
            return $this->buildTextData($toUserName, "请给我一条文本消息作为查询关键词!");
        }
    }
    
    
    private function createTextNode(DOMDocument $dom, DOMNode $parentNode, $name, $content)
    {
        $theNode = $dom->createElement($name);
        $textNode = $dom->createCDATASection($content);
        $theNode->appendChild($textNode);
        $parentNode->appendChild($theNode);
    }
    
    
    private function buildImageData()
    {
//        $dom = new DOMDocument();
//        $root = $dom->createElement("xml");
//        $dom->appendChild($root);
    }
    
    
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