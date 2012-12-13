<?php
require_once("define.php");
require_once("lib" . DIRECTORY_SEPARATOR . "MapResult.php");
$name = "";
$address = "";
$mapImgUrl = "";
if (isset($_GET["pos"]))
{
    $posArr = explode(",", $_GET["pos"]);
    if (count($posArr) >= 4)
    {
        $mapImgUrl = MapResult::getImgUrl($posArr[0], $posArr[1], $posArr[2], $posArr[3]);
    }
    $name = urldecode($_GET["name"]);
    $address = urldecode($_GET["address"]);
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title><?php echo $name; ?></title>
        <meta charset="UTF-8" />
        <link rel="stylesheet" href="static/css/main.css" />
        <link rel="stylesheet" href="static/css/jquery-mobile.css" />
        <script type="text/javascript" src="static/js/jquery.js"></script>
        <script type="text/javascript" src="static/js/jquery-mobile.js"></script>
    </head>

    <body>
        <?php if (!empty($mapImgUrl)): ?>
        <div data-role="dialog">
       
            <div data-role="header" data-theme="d">
                <h1><?php echo $name; ?></h1>
            </div>
            
            <div data-role="content" data-theme="c">
                <p><?php echo $address; ?></p>
                <div>&nbsp;</div>
                <p class="mapImgBox">
                    <img src="<?php echo $mapImgUrl; ?>" />
                </p>
            </div>
        </div>
        <?php endif; ?>
    </body>
</html>