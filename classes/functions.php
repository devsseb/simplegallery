<?

function toHtml($string)
{
	return htmlentities($string, ENT_QUOTES, 'UTF-8');
}

function toUrl($string)
{
	return rawurlencode($string);
}

function array_index($array, $index)
{
	$result = array();
	foreach ($array as $element) {
		$result[$element[$index]] = $element;
	}
	return $result;
}

function go($url)
{
	header('Location:' . $url);
	exit();
}

function mailSend($to, $subject, $message, $from = 'noreply@simplegallery.com')
{
	mail(
		$to,
		$subject,
		$message,
		'MIME-Version: 1.0' . chr(13) . chr(10) .
			'Content-type: text/html; charset=iso-8859-1' . chr(13) . chr(10) .
			'From: ' . $from . chr(13) . chr(10)
	);
}

function success($message, $go = '')
{
	$_SESSION['messages']['success'] = $message;
	if ($go) {
		go($go);
	}
}

function error($message, $go = '')
{
	$_SESSION['messages']['error'] = $message;
	if ($go) {
		go($go);
	}
}

function getDir($dir, $mask = null)
{
	$files = array_diff(scandir($dir), array('..', '.'));
	$files = preg_grep('/' . $mask . '/', $files);
	return array_values($files);
}

function in_dir($dir, $file, $exists = false)
{
	$dir = realpath($dir);
	$element  = realpath(dirname($file)) . '/' . basename($file);
	$return = strpos($element, $dir) === 0;
	if ($return and $exists)
		$return = is_file($file);
	return $return;
}

function stripAccents($string){
	return strtr($string, array_combine(split_unicode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), str_split('aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY')));
}

function split_unicode($str, $l = 0) {
	if ($l > 0) {
	    $ret = array();
	    $len = mb_strlen($str, "UTF-8");
	    for ($i = 0; $i < $len; $i += $l) {
	        $ret[] = mb_substr($str, $i, $l, "UTF-8");
	    }
	    return $ret;
		}
	return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
}

function randomString($length){ 
	$randstr = '';
	for ($i=0; $i<$length; $i++) {
		$randnum = mt_rand(0,61);
		if ($randnum < 10) {
			$randstr .= chr($randnum+48);
		} elseif ($randnum < 36) {
			$randstr.= chr($randnum+55);
		} else {
			$randstr.= chr($randnum+61);
		}
	}
	return $randstr;
} 
?>
