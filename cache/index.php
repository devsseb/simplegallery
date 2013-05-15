<?

function caching_headers ($file) {
	$timestamp = filemtime($file);
    $gmt_mtime = gmdate('r', $timestamp);
    header('ETag: "'.md5($timestamp.$file).'"');
    header('Last-Modified: '.$gmt_mtime);
    header('Cache-Control: private');

    if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
        if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $gmt_mtime || str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == md5($timestamp.$file)) {
            header('HTTP/1.1 304 Not Modified');
            exit();
        }
    }
}

caching_headers($file = 'image.jpg');

$timestamp = filemtime($file);
$gmt_mtime = gmdate('r', $timestamp);

header("Cache-Control:private");
header('ETag: "'.md5($timestamp.$file).'"');
header("Last-Modified: $gmt_mtime");
header("Content-Type: image/gif");

readfile('image.jpg');
exit();
?>
