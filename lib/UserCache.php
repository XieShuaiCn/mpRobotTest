<?php
require_once("DBAct.php");

class UserCache
{
    const QUERY_TYPE_KEYWORD = "KEYWORD";
    const QUERY_TYPE_POSITION = "POSITION";
    
    const QUERY_KEYWORD_EXP = 5;
    const QUERY_POSITION_EXP = 20;
    
    private $tableName = "UserCache";
    private $userKey = "";


    public function __construct($userKey)
    {
        $this->userKey = $userKey;
    }
    
    
    public function addNewQuery($type, $data)
    {
        $type = strtoupper($type);
        if ($type == self::QUERY_TYPE_POSITION)
        {
            $data = json_encode($data);
        }
        else
        {
            $type = self::QUERY_TYPE_KEYWORD;
        }
        $nowStr = date("Y-m-d H:i:s");
        $data = DBAct::escapeString($data);
        $sqlStr = "INSERT INTO {$this->tableName}(UserKey, QueryType, QueryData, QueryTime)
                        VALUES('{$this->userKey}', '{$type}', '{$data}', '{$nowStr}')";
        return DBAct::execute($sqlStr);
    }
    
    
    public function getLastQueryKeyword()
    {
        $expTime = date("Y-m-d H:i:s", time() - self::QUERY_KEYWORD_EXP * 60);
        $sqlStr = "SELECT QueryData FROM {$this->tableName} 
                        WHERE UserKey='{$this->userKey}' AND QueryType='KEYWORD' AND QueryTime>='{$expTime}'
                        ORDER BY ID DESC LIMIT 1";
        $results = DBAct::getOne($sqlStr);
        if ($results)
        {
            return $results["QueryData"];
        }
        return null;
    }
    
    
    public function getLastQueryPosition()
    {
        $expTime = date("Y-m-d H:i:s", time() - self::QUERY_POSITION_EXP * 60);
        $sqlStr = "SELECT QueryData FROM {$this->tableName} 
                        WHERE UserKey='{$this->userKey}' AND QueryType='POSITION' AND QueryTime>='{$expTime}'
                        ORDER BY ID DESC LIMIT 1";
        $results = DBAct::getOne($sqlStr);
        if ($results)
        {
            return json_decode($results["QueryData"], true);
        }
        return null;
    }
}
