<?php
require_once("define.php");
require_once("lib" . DIRECTORY_SEPARATOR . "MapResult.php");
$resultData = null;
$resultList = array();
if (isset($_GET["key"]) && $_GET["key"])
{
    $resultData = MapResult::getResultData($_GET["key"]);
    // 按200米分组
    foreach ($resultData["ResultsListData"] as $val)
    {
        $theRangeStep = floor((int)$val["distance"] / RANGE_GROUP_VAL) + 1;
        if (!isset($resultList[$theRangeStep]))
        {
            $resultList[$theRangeStep] = array();
        }
        $val["mapImgUrl"] =  "getMap.php?name=" . urlencode($val["name"]) . "&address=" . urlencode($val["address"]) . "&pos={$resultData["Lat"]},{$resultData["Lng"]},{$val["lat"]},{$val["lng"]}";
        $resultList[$theRangeStep][] = $val;
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title><?php echo $resultData["QueryStr"] . "   " . $resultData["LabelInfo"]; ?></title>
        <meta charset="UTF-8" />
        <link rel="stylesheet" href="static/css/main.css" />
        <link rel="stylesheet" href="static/css/jquery-mobile.css" />
        <script type="text/javascript" src="static/js/jquery.js"></script>
        <script type="text/javascript" src="static/js/jquery-mobile.js"></script>
    </head>

    <body>
        
        <?php if ($resultData): ?>
        <div data-role="page">
            
            <div data-role="header">
                <h1><?php echo $resultData["QueryStr"]; ?></h1>
            </div><!-- /header -->
        
            <div data-role="content">	
                
                <ul data-role="listview" data-theme="d" data-divider-theme="d">
                <?php foreach ($resultList as $step => $data): ?>
                    <li data-role="list-divider">
                        <b><?php echo (string)($step * RANGE_GROUP_VAL); ?>米</b>范围内
                        <span class="ui-li-count"><?php echo count($data); ?></span>
                    </li>
                    <?php for ($i=0; $i<count($data); $i++): ?>
                    <li>
                        <a href="<?php echo $data[$i]["mapImgUrl"]; ?>" data-rel="dialog" data-transition="turn">
                            <h3><?php echo $data[$i]["name"]; ?></h3>
                            <p><?php echo $data[$i]["address"]; ?></p>
                            <p class="ui-li-aside"><strong><?php echo $data[$i]["distance"]; ?></strong>米</p>
                        </a>
                    </li>
                    <?php endfor; ?>
                <?php endforeach; ?>
                </ul>
                
            </div><!--/content-primary -->		
            
        
            <div data-role="footer">
                <h4><?php echo $resultData["LabelInfo"]; ?></h4>
            </div><!-- /footer -->

        </div><!-- /page -->
        <?php endif; ?>
        
    </body>
</html>
