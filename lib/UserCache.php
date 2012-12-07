<?php
require_once("DBAct.php");

class UserCache
{
    private $tableName = "UserCache";
    private $userKey = "";
    
    private function __construct($userKey)
    {
        $this->userKey = $userKey;
    }
    
    
    private function addTalk($content)
    {
        $content = DBAct::escapeString($content);
        $nowStr = date("Y-m-d H:i:s");
        $sqlStr = "INSERT INTO {$this->tableName}(UserKey, QueryStr, QueryTime)
                        VALUES('{$this->userKey}', '{$content}', '{$nowStr}')";
        return DBAct::execute($sqlStr);
    }
    
    
    private function getLastTalk()
    {
        $sqlStr = "SELECT QueryStr FROM {$this->tableName} WHERE UserKey='{$this->userKey}' ORDER BY ID DESC LIMIT 1";
        $results = DBAct::getOne($sqlStr);
        if ($results)
        {
            return $results["QueryStr"];
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
