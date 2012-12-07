<?php
require_once("define.php");
require_once("lib" . DIRECTORY_SEPARATOR . "MapResult.php");
$resultData = array();
if (isset($_GET["key"]) && $_GET["key"])
{
    $resultData = MapResult::getResultData($_GET["key"]);
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title> MAP </title>
        <meta charset="UTF-8" />
        <style type="text/css">
            * {
                color:#333333;
                margin:0;
                padding:0;
            }
            body {
                width:300px;
                margin:0 auto;
                margin-top:15px;
            }
            hr {
                margin:5px auto;
            }
            .title h1 {
                line-height:48px;
            }
            .title h4 {
                line-height:30px;
            }
            ol.list {
                list-style-position:inside;
            }
            ol.list li {
                font-size:13px;
                margin-bottom:10px;
            }
            ol.list li .name {
                line-height:20px;
            }
            ol.list li a {
                font-weight:bold;
                color:#0099CC;
                text-decoration:none;
            }
            ol.list li .name span {
                font-weight:normal;
            }
            ol.list li .address {
                line-height:16px;
                margin-left:25px;
            }
        </style>
        <script type="text/javascript" src="static/js/jquery.js"></script>
        <!--
            <script type="text/javascript" src="static/js/showdown.js"></script>
            <script type="text/javascript" src="static/js/showdown-extensions/github.js"></script>
            <script type="text/javascript" src="static/js/showdown-extensions/prettify.js"></script>
            <script type="text/javascript" src="static/js/showdown-extensions/table.js"></script>
            <script type="text/javascript" src="static/js/showdown-extensions/twitter.js"></script>
            <script type="text/javascript">
                var dataCreate = function() {
                    var converter = new Showdown.converter();
                    var text = $("#content").val();
                    var html = converter.makeHtml(text);
                    $("#result").html(html);
                };
            </script>
        -->
        <script type="text/javascript">
            var reloadCount = {};
            var imageReload = function(o) {
                var src = $(o).attr("src");
                if (typeof(reloadCount[src]) == "undefined") {
                    reloadCount[src] = 0;
                } else {
                    reloadCount[src]++;
                }
                if (reloadCount[src] >= 5) {
                    return;
                }
                var rnd = parseInt(Math.random() * 100000, 10).toString();
                src = src.replace(/&rnd=\d+/, "&rnd=" + rnd);
                $(o).attr("src", src);
            };
            
            var getMapImg = function(o) {
                $(".mapImg img").hide();
                var mapImgObj = $(o).closest("li").find(".mapImg");
                if (mapImgObj.children("img").length <= 0) {
                    var imgSrc = $(o).attr("imgSrc") + "&rnd=" + parseInt(Math.random() * 100000, 10).toString();
                    mapImgObj.html('<img src="' + imgSrc + '" onerror="imageReload(this);" />');
                }
                mapImgObj.children("img").show();
            };
        </script>
    </head>

    <body>
        <div id="result">
            <div class="title">
                <h1><center><?php echo $resultData["QueryStr"]; ?></center></h1>
                <h4><center><?php echo $resultData["LabelInfo"]; ?></center></h4>
                <hr />
            </div>
            <ol class="list">
            <?php foreach ($resultData["ResultsListData"] as $val): ?>
                <li>
                    <div class="name">
                        <a href="javascript:;" imgSrc="<?php echo MapResult::getImgUrl($resultData["Lat"], $resultData["Lng"], $val["lat"], $val["lng"]); ?>" onclick="getMapImg(this);"><?php echo $val["name"]; ?></a>
                        <span>(<?php echo $val["distance"]; ?>m)</span>
                    </div>
                    <div class="address"><?php echo $val["address"]; ?></div>
                    <div class="mapImg"></div>
                </li>
            <?php endforeach; ?>
            </ol>
        </div>
    </body>
</html>
