<?php
require_once('./progressive_utils.inc.php');
$url = htmlspecialchars($_REQUEST['img']);
$scans = GetImageScans($url, $info);
$width = $info['width'];
$height = $info['height'];
$baseline = $info['baseline'];
$divWidth = $width * 2;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="google" value="notranslate" />
<meta http-equiv="Content-Language" content="en" />
<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/themes/start/jquery-ui.css" type="text/css" media="all" />
<style type="text/css">
    div.hidden {display:none;}
    div.interactive {position:relative; top:0; left:0;}
    div.cleared {float:none; clear:both;}
    #slider {width:400px;}
<?php    
    echo "#interactive {width:{$divWidth}px;}\n";
    echo "img {width:{$width}px;height:{$height}px;}\n";
?>
</style>
</head>
<body>
<?php
if (isset($scans) && is_array($scans)) {
    $scan_count = count($scans) - 1;
    $mid = (int)($scan_count / 2);
    echo "<h1>Progressive Jpeg Demonstration</h1>";
    echo "Original Image: <a href=\"$url\">$url</a><br>";
    echo "<a href=\"index.html\">New Comparison</a>";
    echo "<h2>Interactive View:</h2>";
    echo "<p>The image on the left (or above) is the progressive image and the one on the right is top-down baseline.<br>";
    echo "Move the slider to see the progression of the scans.<br>";
    echo "Hover the mouse over the image to see the original image for a quick A/B comparison.</p>";
    echo "<button id=\"animate\" onclick=\"ToggleAnimation();\">Start Animation</button><br>";
    echo "<div id=\"interactive\">";
    echo "<div id=\"slider\"></div>";
    $count = count($scans) - 1;
    echo "<div id=\"interactive-images\">";
    while ($count > 0) {
        $visibility = 'hidden';
        if ($count == $mid)
            $visibility = 'visible';
        echo "<div id=\"interactive-$count\" class=\"interactive $visibility\">\n";
        DisplayImage($scans, $count);
        $count--;
        echo "</div>\n";
    }
    echo "<div class=\"cleared\"></div></div>";
    echo "</div>";
    echo "<h2>Individual Images:</h2>";
    $count = count($scans) - 1;
    while ($count > 0) {
        echo "<div id=\"scans-$count\">\n";
        DisplayImage($scans, $count);
        $count--;
        echo "</div>\n";
    }
} else {
    echo "Error processing image";
}

function DisplayImage(&$scans, $count) {
    global $width;
    global $baseline;
    $out = '';
    for ($i = 0; $i < $count; $i++)
        $out .= $scans[$i];
    $bytes = strlen($out);
    echo "$count scans - $bytes bytes<br>";
    echo '<img src="data:image/jpeg;base64,';
    echo base64_encode($out);
    echo '">';
    if (is_file($baseline)) {
        if ($count >= count($scans) - 1)
            $bytes = filesize($baseline);
        $file = fopen($baseline, 'rb');
        if ($file) {
            echo '<img src="data:image/jpeg;base64,';
            echo base64_encode(fread($file, $bytes));
            echo '">';
            fclose($file);
        }
    }
    echo '<br><br>';
}

?>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js"></script>
<script>
<?php
echo "\$(\"#slider\").slider({ min: 1, max: $scan_count, step: 1, value: $mid });\n";
echo "var count = $scan_count;\n";
echo "var selected = $mid;\n";
?>
var timer;
$("#slider").on("slide", function(event, ui) {
    selected = ui.value;
    ShowSelected();
});
$("#interactive-images").hover(
    function () {
        if (timer == undefined) {
            $('#interactive-' + count).show();
            for (i = 1; i < count; i++) {
                $('#interactive-' + i).hide();
            }
        }
    }, 
    function () { 
        ShowSelected();
    }
);
function ShowSelected() {
    $('#interactive-' + selected).show();
    for (i = 1; i <= count; i++) {
        if (i != selected) {
            $('#interactive-' + i).hide();
        }
    }
};
function IncrementProgress() {
    var delay = 100;
    selected++;
    if (selected == count)
        delay = 1000;
    if (selected > count)
        selected = 1;
    $("#slider").slider( "value", selected );
    ShowSelected();
    timer = setTimeout('IncrementProgress()', delay);
};
function ToggleAnimation() {
    if (timer == undefined) {
        $("#animate").text('Stop Animation');
        selected = 1;
        $("#slider").slider( "value", selected );
        ShowSelected();
        timer = setTimeout('IncrementProgress()', 100);
    } else {
        $("#animate").text('Start Animation');
        clearTimeout(timer);
        timer = undefined;
    }
};
</script>
</body>
</html>