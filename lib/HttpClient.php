<?php
/**
 * HttpClient 模拟的实现
 *
 * @author Caliburn
 */


class HttpClientException extends Exception
{
}


class HttpClientUploadFile
{
    public $fileName = "";          // 文件名
    public $fileType = "";          // 文件类型
    public $fileContent = "";       // 文件内容
    
    public function __construct($fileName, $fileContent, $fileType="text/plain")
    {
        $this->fileName = $fileName;
        $this->fileContent = $fileContent;
        $this->fileType = $fileType;
    }
}



class HttpClient
{
    const VAR_ACCEPT = "text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,image/jpeg,image/gif,*/*";
    
    private $host;
    private $port;
    private $cookies = array();
    private $timeout = 5;
    private $userAgent = "Mozilla/5.0 (MSIE 9.0; Windows NT 6.1; Trident/5.0)";     // 默认UA为IE9
    private $acceptLanguage = "zh-CN";
    private $useGzip = true;
    private $keepAlive = true;
    private $useCache = false;
    private $referer;
    
    // 站点验证
    private $authData = array("username"=>null, "password"=>null);
    
    private $boundary;                  // 分割线
    private $lastErrorData;
    
    public function __construct($host, $port=80)
    {
        $hostData = parse_url($host);
        if (isset($hostData["host"]))
        {
            $this->host = $hostData["host"];
            $this->referer = $host;
        }
        else
        {
            $this->host = trim($host);
            $this->referer = "http://" . $host;
        }
        $this->port = (int)$port;
        $this->host = strtolower($this->host);
        
        $this->boundary = "---------------------------" . substr(md5(time()), 0, 10);
        
        // 最后错误信息
        $this->lastErrorData = array(
            "no" => null,
            "msg" => null,
        );
    }
       
    
    /**
     * 生成POST每数组的查询字符串
     * @param string $key
     * @param array|string $val
     * @return string
     */
    private function buildSubPostContent($key, $val)
    {
        $ret = "";
        if (is_array($val))
        {
            foreach ($val as $d)
            {
                $ret .= $this->buildSubPostContent($key . "[]", $d);
            }
        }
        else
        {
            $ret .= "--{$this->boundary}\r\n";
            if ($val instanceof HttpClientUploadFile)
            {
                // 上传的文件
                $ret .="Content-Disposition: form-data; name=\"{$key}\"; filename=\"{$val->fileName}\"\r\n";
                $ret .= "Content-Type: {$val->fileType}\r\n";
                $ret .= "\r\n";
                $ret .= "{$val->fileContent}\r\n";
                $ret .= "--{$this->boundary}--\r\n";
            }
            else
            {
                // 普通文本
                $ret .= "Content-Disposition: form-data; name=\"{$key}\"\r\n";
                $ret .= "\r\n";
                $ret .= "{$val}\r\n";
            }
        }
        return $ret;
    }
    
    
    /**
     * 生成POST查询字符串
     * @param array $data
     * @return string
     */
    private function buildPostString(array $data)
    {
        $querystring = "";
        if (count($data) > 0)
        {
            foreach ($data as $key => $val)
            {
                $querystring .= $this->buildSubPostContent($key, $val);
            }
        }
        return $querystring;
    }
    
    
    /**
     * 生成简单的每数组的查询字符串
     * @param string $key
     * @param array|string $val
     * @return string
     */
    private function buildSubQueryString($key, $val)
    {
        $ret = "";
        if (is_array($val))
        {
            foreach ($val as $d)
            {
                if (!empty($ret))
                {
                    $ret .= "&";
                }
                $ret .= $this->buildSubQueryString($key . "[]", $d);
            }
        }
        else
        {
            $ret .= $key . "=" . urlencode($val);
        }
        return $ret;
    }
    
    
    /**
     * 生成查询字符串(用于GET或简单POST)
     * @param array|string $data
     * @return string
     */
    private function buildQueryString($data)
    {
        $ret = "";
        if (is_array($data))
        {
            foreach ($data as $key => $val)
            {
                if (!empty($ret))
                {
                    $ret .= "&";
                }
                $ret .= $this->buildSubQueryString($key, $val);
            }
        }
        else
        {
            $ret = $data;
        }
        return $ret;
    }
    
    
    /**
     * 生成Request的Header组合字符串
     * @param string $uri
     * @param string|array $postData
     * @return string
     */
    private function buildRequestStr($uri, $postData=null)
    {
        $uriData = parse_url($uri);
        
        $postQueryStr = "";
        $method = "GET";            // 默认GET
        if ($postData)
        {
            $postQueryStr = $this->buildPostString($postData);
        }
        if (!empty($postQueryStr))
        {
            $method = "POST";
        }
        
        $headers = array();
        $headers[] = "{$method} {$uri} HTTP/1.0";   // Using 1.1 leads to all manner of problems, such as "chunked" encoding
        $headers[] = "Host: {$this->host}";
        $headers[] = "User-Agent: {$this->userAgent}";
        $headers[] = "Accept: " . self::VAR_ACCEPT;
        if ($this->useGzip)
        {
            $headers[] = "Accept-encoding: gzip";
        }
        $headers[] = "Accept-language: {$this->acceptLanguage}";
        if ($this->referer)
        {
            $headers[] = "Referer: {$this->referer}";
        }
        if ($method == "POST" && $this->keepAlive)
        {
            $headers[] = "Connection: Keep-Alive";
        }
        if (!$this->useCache)
        {
            $headers[] = "Cache-Control: no-cache";
        }
        // Cookies
        $cookie = "";
        if (isset($this->cookies["/"]))     // 全局Cookie
        {
            foreach ($this->cookies["/"] as $key => $value)
            {
                $cookie .= "{$key}={$value}; ";
            }
        }
        if (isset($uriData["path"]) && $uriData["path"] != "/")     // 指定Cookie
        {
            $thePath = $uriData["path"];
            if (isset($this->cookies[$thePath]))
            {
                foreach ($this->cookies[$thePath] as $key => $value)
                {
                    $cookie .= "{$key}={$value}; ";
                }
            }
        }
        if (!empty($cookie))
        {
            $headers[] = "Cookie: " . $cookie;
        }
        // Authorization
        if (!is_null($this->authData["username"]) && !is_null($this->authData["password"]))
        {
            $headers[] = 'Authorization: BASIC ' . base64_encode($this->authData["username"] . ':' . $this->authData["password"]);
        }
        // 按POST提交的方式
        if ($method == "POST")
        {
            //$headers[] = "Content-Type: application/x-www-form-urlencoded";
            $headers[] = "Content-Type: multipart/form-data, boundary={$this->boundary}";       // 兼容文件上传
            $headers[] = "Content-Length: " . strlen($postQueryStr);
            // POST的内容
            $headers[] = "";
            $headers[] = $postQueryStr;
        }
        // 没有换行不会提交?
        $headers[] = "";
        $headers[] = "";
        
        $request = implode("\r\n", $headers);
        return $request;
    }
    
    
    /**
     * 分解Request的Cookie, 并暂存
     * @param array|string $cookies
     */
    private function parseRequestCookie($cookies)
    {
        $cookieArr = array();
        if (is_array($cookies))
        {
            $cookieArr = $cookies;
        }
        else
        {
            $cookieArr = array($cookies);
        }
        
        $results = array();
        foreach ($cookieArr as $cdata)
        {
            $match = array();
            if (preg_match('/(?P<key>[^=]+)=(?P<val>[^;]+);(\\s*path=(?P<path>.+))*/i', $cdata, $match))
            {
                $key = $match["key"];
                $val = $match["val"];
                $path = "/";
                if (isset($match["path"]) && !empty($match["path"]))
                {
                    $path = strtolower($match["path"]);
                }
                if (!isset($results[$path]))
                {
                    $results[$path] = array();
                }
                $results[$path][$key] = $val;
            }
        }
        
        $this->cookies = $results;
    }
    
    
    /**
     * 整理返回的MAP, 如果存在多个相似的KEY,则按数组返回
     * @param array $data
     * @param string $key
     * @param string $val
     */
    private function pushArrData(array &$data, $key, $val)
    {
        if (isset($data[$key]))
        {
            if (is_array($data[$key]))
            {
                $data[$key][] = $val;
            }
            else
            {
                $data[$key] = array($data[$key], $val);
            }
        }
        else
        {
            $data[$key] = $val;
        }
    }
    
    
    /**
     * 解开Response得到的内容
     * @param array $contentArr
     * @return array
     * @throws HttpClientException
     */
    private function parseResponseArr(array $contentArr)
    {
        // 第一行应该是返回状态的信息
        if (count($contentArr) > 0)
        {
            $retArr = array(
                "Header" => array(),        // 头信息
                "HeaderTidy" => array(),    // 整理过的头信息, 所有KEY按小写编排
                "Contents" => "",
            );
            
            $statusStr = $contentArr[0];
            $match = array();
            if (preg_match('/HTTP\/(?P<version>\\d\\.\\d)\\s*(?P<code>\\d+)\\s*(?P<str>.*)/', $statusStr, $match))
            {
                //$httpVersion = $match["version];  // HTTP版本
                $httpStatusCode = $match["code"];   // 请求状态代码
                //$httpStatusStr = $match["str];    // 请求状态结果
            }
            else
            {
                // HTTP STATUS 不存在
                throw new HttpClientException("NO HTTP STATUS \r\n------\r\n\r\n" . implode("\r\n", $contentArr));
            }
            
            if (trim($httpStatusCode) != "200")
            {
                throw new HttpClientException("The Request Faild", $httpStatusCode);
            }
            
            $lineFlag = 1;
            // 分解Header
            for (; $lineFlag<count($contentArr); $lineFlag++)
            {
                if (trim($contentArr[$lineFlag]) == "")
                {
                    break;              // 分行, 下面是正文内容
                }
                
                $match = array();
                if (!preg_match('/(?P<key>[^:]+):\\s*(?P<val>.*)/', $contentArr[$lineFlag], $match))
                {
                    continue;
                }
                $key = trim($match["key"]);
                $tKey = strtolower($key);
                $val = trim($match["val"]);
                
                // 重复的情况转成数组
                $this->pushArrData($retArr["Header"], $key, $val);
                $this->pushArrData($retArr["HeaderTidy"], $tKey, $val);
            }
            ++$lineFlag;
            // 添加正文
            for (; $lineFlag<count($contentArr); $lineFlag++)
            {
                $retArr["Contents"] .= $contentArr[$lineFlag];
            }
            
            // 解析返回的Cookie数据
            if (isset($retArr["HeaderTidy"]["set-cookie"]))
            {
                $this->parseRequestCookie($retArr["HeaderTidy"]["set-cookie"]);
            }
            
            return $retArr;
        }
        
        // 没有获取到值
        throw new HttpClientException("Request Faild, GET EMPTY ?");
    }
    
    
    /**
     * 提交查询, 并返回结果
     * @param string $uri
     * @param string|array $postData
     * @return array
     * @throws HttpClientException
     */
    public function doRequest($uri, $postData=null)
    {
        if (!$fp = @fsockopen($this->host, $this->port, $this->lastErrorData["no"], $this->lastErrorData["msg"], $this->timeout))
        {
            // Set error message
            switch ($this->lastErrorData["no"])
            {
                case -3:
                    throw new HttpClientException("Socket creation failed", -3);
                case -4:
                    throw new HttpClientException("DNS lookup failure", -4);
                case -5:
                    throw new HttpClientException("Connection refused or timed out", -5);
                default:
                    throw new HttpClientException("Connection failed {$this->lastErrorData["msg"]}", $this->lastErrorData["no"]);
            }
        }
        
        socket_set_timeout($fp, $this->timeout);
        
        $uriData = parse_url($uri);
        if (isset($uriData["host"]))
        {
            if (strtolower($uriData["host"]) != $this->host)
            {
                throw new HttpClientException("{$uriData["host"]} Not Match {$this->host}");
            }
            else
            {
                $uri = "";
                $uri .= isset($uriData["path"]) ? $uriData["path"] : "";
                $uri .= isset($uriData["query"]) ? "?" . $uriData["query"] : "";
            }
        }
                
        $requestStr = $this->buildRequestStr($uri, $postData);
        fwrite($fp, $requestStr);
                        
        $responseArr = array();
        while (!feof($fp))
        {
            $line = fgets($fp, 4096);
            $responseArr[] = $line;
        }        
        fclose($fp);
        
        $responseData = $this->parseResponseArr($responseArr);
        
        // 内容解压缩
        if (isset($responseData["HeaderTidy"]["content-encoding"]) && strtolower($responseData["HeaderTidy"]["content-encoding"]) == "gzip")
        {
            // http://www.php.net/manual/en/function.gzencode.php
            $responseData["Contents"] = gzinflate(substr($responseData["Contents"], 10));
        }
        
        return $responseData;  
    }
    
    
    /**
     * GET方式提交
     * @param string $uri
     * @param array $queryData
     * @return string
     */
    public function getUploadString($uri, array $queryData=array())
    {
        $queryStr = $this->buildQueryString($queryData);
        if ($queryStr)
        {
            if (strpos($uri, "?") !== false)
            {
                $uri .= "&";
            }
            else
            {
                $uri .= "?";
            }
            $uri .= $queryStr;
        }
        $result = $this->doRequest($uri, null);
        return $result["Contents"];
    }
    
    
    /**
     * POST方式提交数据
     * @param string $uri
     * @param array $queryData
     * @return string
     */
    public function postUploadString($uri, array $queryData)
    {
        if (is_array($queryData) && count($queryData) > 0)
        {
            $result = $this->doRequest($uri, $queryData);
            return $result["Contents"];
        }
        else
        {
            return $this->get($uri);
        }
    }
    
    
    /**
     * 设置Cookie
     * @param string $key
     * @param string $value
     * @param string $path
     */
    public function setCookie($key, $value, $path="/")
    {
        if (!isset($this->cookies[$path]))
        {
            $this->cookies[$path] = array();
        }
        $this->cookies[$path][$key] = $value;
    }
    
    
    /**
     * 获取Cookie数据
     * @param string $path
     * @return array
     */
    public function getCookie($path="/")
    {
        if (isset($this->cookies[$path]))
        {
            return $this->cookies[$path];
        }
        return array();
    }
    
    
    /**
     * 获取最后的Socket错误信息
     * @return array
     */
    public function getLastSocketError()
    {
        return $this->lastErrorData;
    }
    
    
    /**
     * 设置超时时间
     * @param int $t
     */
    public function setTimeout($t)
    {
        $t = (int)$t;
        if ($t <= 0)
        {
            $t = 1;
        }
        $this->timeout = $t;
    }
    
    
    /**
     * UA设置
     * @param string $data
     */
    public function setUserAgent($data)
    {
        $this->userAgent = $data;
    }
    
    /**
     * 返回UA信息
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }
    
    
    /**
     * 语言设置
     * @param string $data
     */
    public function setAcceptLanguage($data)
    {
        $this->acceptLanguage = $data;
    }
    
    /**
     * 返回语言信息
     * @return string
     */
    public function getAcceptLanguage()
    {
        return $this->acceptLanguage;
    }
    
    
    /**
     * 设置是否使用GZIP发送请求
     * @param boolean $b
     */
    public function setUseGzip($b)
    {
        $b = (bool)$b;
        $this->useGzip = $b;
    }
    
    /**
     * 设置是否KEEP ALIVE
     * @param boolean $b
     */
    public function setKeepAlive($b)
    {
        $b = (bool)$b;
        $this->keepAlive = $b;
    }
    
    /**
     * 设置是否使用缓存
     * @param boolean $b
     */
    public function setUseCache($b)
    {
        $b = (bool)$b;
        $this->useCache = $b;
    }
    
    
    /**
     * 设置Referer
     * @param string $data
     */
    public function setReferer($data)
    {
        $this->referer = $data;
    }
    
    /**
     * 返回Referer信息
     * @return string
     */
    public function getReferer()
    {
        return $this->referer;
    }

}