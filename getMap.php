<?php
require_once("define.php");
require_once("lib" . DIRECTORY_SEPARATOR . "MapResult.php");
$content = "";
if (isset($_GET["key"]) && $_GET["key"])
{
    $content = MapResult::getMakeDownStr($_GET["key"]);
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
            }
            body {
                width:400px;
                margin:0 auto;
            }
            #content {
                display:none;
            }
        </style>
        <script type="text/javascript" src="static/js/jquery.js"></script>
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
    </head>

    <body onload="dataCreate();">
        <textarea id="content"><?php echo $content; ?></textarea>
        <div id="result"></div>
    </body>
</html>
