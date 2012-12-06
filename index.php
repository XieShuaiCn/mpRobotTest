<?php

require_once("define.php");
require_once("Reply.php");

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


$postData = null;
if ($_POST)
{
    $postData = $GLOBALS["HTTP_RAW_POST_DATA"];
}
else if (isset($_GET["trying"]))
{
    $postData = "
<xml>
    <ToUserName><![CDATA[KangCai TO]]></ToUserName>
    <FromUserName><![CDATA[KangCai From]]></FromUserName>
    <CreateTime>1348831860</CreateTime>
    <MsgType><![CDATA[text]]></MsgType>
    <Content><![CDATA[AABBCCDDEE]]></Content>
</xml> 
";
}


// Robot
if ($postData)
{
    $reply = new Reply();
    $retConnect = $reply->responseMsg($postData);
    echo $retConnect;
}