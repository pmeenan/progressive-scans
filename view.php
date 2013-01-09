<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<meta name="google" value="notranslate" />
<meta http-equiv="Content-Language" content="en" />
</head>
<body>
<?php
require_once('./progressive_utils.inc.php');

$scans = GetImageScans($_REQUEST['img']);
if (isset($scans) && is_array($scans)) {
    $count = count($scans) - 1;
    while ($count > 0) {
        $out = '';
        for ($i = 0; $i < $count; $i++)
            $out .= $scans[$i];
        echo "$count scans - " . strlen($out) . ' bytes<br>';
        echo '<img src="data::image/jpeg;base64,';
        echo base64_encode($out);
        echo '"><br><br>';
        $count--;
    }
} else {
    echo "Error processing image";
}
?>
</body>
</html>