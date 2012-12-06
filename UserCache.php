<?php

class UserCache
{
    private $cachePath = USER_CACHE_PATH;
    private $fileName = "";
    private $data = array(
        "talkList" => array(),                  // 谈话信息, 时间,内容 
    );
    
    private function __construct($user)
    {
        $this->fileName = $this->cachePath . DIRECTORY_SEPARATOR . sha1($user);
        if (file_exists($this->fileName))
        {
            $this->data = json_decode(file_get_contents($this->fileName), true);
        }
    }
    
    
    private function addTalk($content)
    {
        $this->data["talkList"][] = array(date("Y-m-d H:i:s"), $content);
        file_put_contents($this->fileName, json_encode($this->data));
    }
    
    
    private function getLastTalk()
    {
        if (count($this->data["talkList"]) > 0)
        {
            $index = count($this->data["talkList"]) - 1;
            return $this->data["talkList"][$index][1];
        }
        return "";
    }
    
    
    public static function simpleAddTalk($user, $content)
    {
        $o = new UserCache($user);
        $o->addTalk($content);
        unset($o);
    }
    
    
    public static function simpleGetLastTalk($user)
    {
        $o = new UserCache($user);
        $content = $o->getLastTalk();
        unset($o);
        return $content;
    }
}
