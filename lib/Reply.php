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
        $msgContent = strip_tags((string)$reqObj->Content);
        UserCache::simpleAddTalk($toUserName, $msgContent);
        return $this->buildTextData($toUserName, "您查询的关键词为:{$msgContent},请输入地址信息,返回结果.");
    }
    
    
    private function replyLocationType(SimpleXMLElement $reqObj, $toUserName)
    {
        $lat = (float)$reqObj->Location_X;
        $lng = (float)$reqObj->Location_Y;
        $label = (string)$reqObj->Label;
        //$scale = (int)$reqObj->Scale;       // 缩放大小
        $query = UserCache::simpleGetLastTalk($toUserName);
        if ($query)
        {
            // 实现逻辑
            $data = $this->placeQuery->getResults($lat, $lng, $query, $label);
            $iKey = $this->placeQuery->getInverseKey($lat, $lng, $query);
            $retTextArr = array();
            $retTextArr[] = "{$query} 本次搜索共找到 " . count($data) . " 个结果";
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
            $retTextArr[] .= "  ** 列出的是直线距离, 现只能计算直线距离...";
            // link
            $retTextArr[] = "http://113.11.199.202/test/mpRobotTest/MAP{$iKey}";
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