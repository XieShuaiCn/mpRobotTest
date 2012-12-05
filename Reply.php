<?php

class Reply
{
    private $myUserName = "";
    
    
    public function responseMsg($content)
    {
        $content = trim($content);
        $retText = "";
        if (!empty($content)) 
        {
            $reqObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $msgType = strtolower($reqObj->MsgType);
            $this->myUserName = $reqObj->ToUserName;
            $toUserName = $reqObj->FromUserName;
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
        $picUrl = $reqObj->PicUrl;
        return $this->buildTextData($toUserName, "图片不能识别！！");
    }
    
    
    private function replyTextType(SimpleXMLElement $reqObj, $toUserName)
    {
        $msgContent = $reqObj->Content;
        return $this->buildTextData($toUserName, "文本消息待实现！");
    }
    
    
    private function replyLocationType(SimpleXMLElement $reqObj, $toUserName)
    {
        $lat = (float)$reqObj->Location_X;
        $lon = (float)$reqObj->Location_Y;
        // 实现逻辑
    }
    
    
    private function createTextNode(DOMDocument $dom, DOMNode $parentNode, $name, $content)
    {
        $theNode = $dom->createElement($name);
        $textNode = $dom->createTextNode($content);
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
        
        return $dom->saveXML();
    }

}