<?php

require_once("define.php");
require_once("lib" . DIRECTORY_SEPARATOR . "Reply.php");
require_once("lib" . DIRECTORY_SEPARATOR . "Valid.php");

if ($_GET)
{
    if (isset($_GET["signature"]) && isset($_GET["timestamp"]) && isset($_GET["nonce"]) && isset($_GET["echostr"]))
    {
        $validResult = Valid::check($_GET["signature"], $_GET["timestamp"], $_GET["nonce"], TOKEN);
        if ($validResult)
        {
            echo $_GET["echostr"];
        }
    }
}

if (isset($GLOBALS["HTTP_RAW_POST_DATA"]))
{
    $postData = $GLOBALS["HTTP_RAW_POST_DATA"];
}
else if (isset($_GET["trying"]))
{
    $postData = "
    <xml>
        <ToUserName><![CDATA[toUser]]></ToUserName>
        <FromUserName><![CDATA[fromUser]]></FromUserName>
        <CreateTime>1351776360</CreateTime>
        <MsgType><![CDATA[location]]></MsgType>
        <Location_X>31.192055</Location_X>
        <Location_Y>121.609123</Location_Y>
        <Scale>20</Scale>
        <Label><![CDATA[不清楚记录啥]]></Label>
     </xml> 
";
//    $postData = "
//    <xml>
//        <ToUserName><![CDATA[toUser]]></ToUserName>
//        <FromUserName><![CDATA[fromUser]]></FromUserName>
//        <CreateTime>1348831860</CreateTime>
//        <MsgType><![CDATA[text]]></MsgType>
//        <Content>便利店</Content>
//    </xml> 
//";
}


// Robot
if ($postData)
{
    $reply = new Reply();
    $retConnect = $reply->responseMsg($postData);
    echo $retConnect;
}