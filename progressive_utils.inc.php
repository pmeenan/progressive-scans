<?php
function GetImageScans($src, &$info) {
    $scans = null;
    $hash = sha1($src);
    $dir = './tmp/' . $hash[0];
    if (!is_dir($dir))
        mkdir($dir);
    $dir = realpath($dir);
    $original = "$dir/$hash.original.jpg";
    $progressive = "$dir/$hash.jpg";
    $baseline = "$dir/$hash-baseline.jpg";
    if (!is_file($original)) {
        file_put_contents($original, file_get_contents($src));
    }
    if (is_file($original)) {
        $size = getimagesize($original);
        $info['original'] = $original;
        $info['originalSize'] = filesize($original);
        $info['width'] = $size[0];
        $info['height'] = $size[1];
        if (!is_file($baseline)) {
            $cmd = 'jpegtran -optimize -copy none -outfile ' . escapeshellarg($baseline) . ' ' . escapeshellarg($original);
            exec($cmd, $result);
        }
        if (is_file($baseline)) {
            $info['baseline'] = $baseline;
            $info['baselineSize'] = filesize($baseline);
        }
        if (!is_file($progressive)) {
            $cmd = 'jpegtran -progressive -optimize -copy none -outfile ' . escapeshellarg($progressive) . ' ' . escapeshellarg($original);
            exec($cmd, $result);
        }
        if (is_file($progressive)) {
            $info['progressive'] = $progressive;
            $info['progressiveSize'] = filesize($progressive);
            $file = fopen($progressive, 'rb');
            if ($file) {
                $bytes = fread($file, $info['progressiveSize']);
                // process the jpeg markers
                $i = 0;
                $scan_start = 0;
                $size = strlen($bytes);
                while (FindNextScan($bytes, $size, $i)) {
                    $scan_length = $i - $scan_start;
                    $scans[] = substr($bytes, $scan_start, $scan_length);
                    $scan_start = $i;
                }
                fclose($file);
            }
        }
    }
    return $scans;
}

function FindNextScan(&$bytes, $size, &$i) {
    $found = false;
    if ($i < $size) {
        while (!$found && FindNextMarker($bytes, $size, $i, $marker, $marker_length)) {
            if (bin2hex($marker) == 'ffda') {
                $found = true;
            }
            $i += $marker_length;
        }
        if (!$found) {
            $found = true;
            $i = $size;
        }
    }
    return $found;
}

function FindNextMarker(&$bytes, $size, &$i, &$marker, &$marker_length) {
    $marker_length = 0;
    $marker = null;
    $found = false;
    $nolength = array('d0','d1','d2','d3','d4','d5','d6','d7','d8','d9','01');
    $sos = 'da';
    if ($i < $size) {
      $val = dechex(ord($bytes[$i]));
      if ($val == 'ff') {
          $marker = $bytes[$i];
          // ff can repeat, the actual marker comes from the first non-ff
          while ($val == 'ff') {
            $i++;
            $val = dechex(ord($bytes[$i]));
          }
          $marker .= $bytes[$i];
          $i++;
          if (in_array($val, $nolength)) {
              $found = true;
          } elseif($val == $sos) {
              // image data
              $j = $i + 1;
              $next_marker = $size;
              while ($j < $size - 1 && !$found) {
                  $val = dechex(ord($bytes[$j]));
                  if ($val == 'ff') {
                      $k = $j + 1;
                      $val = dechex(ord($bytes[$k]));
                      if ($val != '00') {   // escaping
                        while ($k < $size = 1 && $val == 'ff') {
                            $k++;
                            $val = dechex(ord($bytes[$k]));
                        }
                        $next_marker = $j;
                        $found = true;
                      }
                  }
                  $j++;
              }
              $marker_length = $next_marker - $i;
          } elseif ($i + 1 < $size) {
            $l1 = ord($bytes[$i]);
            $l2 = ord($bytes[$i+1]);
            $marker_length = $l1 * 256 + $l2;
            $found = true;
          }
      }
    }
    return $found;
}
?>
