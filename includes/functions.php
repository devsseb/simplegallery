<?

function toHtml($string)
{
	return htmlentities((string)$string, ENT_QUOTES, 'UTF-8');
}

function toUrl($string)
{
	return rawurlencode($string);
}

function array_index($array, $index)
{
	$result = array();
	foreach ($array as $element)
		$result[is_array($element) ? $element[$index] : $element->$index] = $element;
	return $result;
}

function go($url)
{
	if ($debug = debug() or headers_sent())
		echo '<p style="text-align:center;font-weight:bold;">Redirecting to <br /><i>'.toHtml($url).'</i><br />...</p><script type="text/javascript">setTimeout("document.location.href=\''.$url.'\'", ' . ($debug ? 1200 : 0) . ');</script>';
	else
		header('Location:' . $url);
	exit();
}

function mailCheck($email)
{
	return (bool)preg_match('/^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$/', $email);
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
	if ($go)
		go($go);
}

function error($message, $go = '')
{
	$_SESSION['messages']['error'] = $message;
	if ($go)
		go($go);
}

function information($message, $go = '')
{
	$_SESSION['messages']['information'] = $message;
	if ($go)
		go($go);
}
/*
function getDir($dir, $pattern = null)
{
	$files = array_diff(scandir($dir), array('..', '.'));
	if ($pattern)
		$files = preg_grep($pattern, $files);
	return array_values($files);
}

function inDir($dir, $file, $exists = false)
{
	$dir = realpath($dir);
	$element  = realpath(dirname($file)) . '/' . basename($file);
	$return = strpos($element, $dir) === 0;
	if ($return and $exists)
		$return = is_file($file);
	return $return;
}
*/
function stripAccents($string)
{
	return strtr($string, array_combine(split_unicode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), str_split('aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY')));
}

function split_unicode($str, $l = 0)
{
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

function randomString($length)
{ 
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

function imagesize($img)
{
	list($width, $height) =
		is_string($img) ?
			getimagesize($img) :
			array(imagesx($img), imagesy($img));
	return object('width', $width, 'height', $height);
}

function imageshadow($img, $size, $color = '#000')
{
	$imgSize = imagesize($img);

	$color = str_replace('#', '', $color);

	if(strlen($color) == 3) {
		$r = hexdec(substr($color, 0, 1) . substr($color, 0, 1));
		$g = hexdec(substr($color, 1, 1) . substr($color, 1, 1));
		$b = hexdec(substr($color, 2, 1) . substr($color, 2, 1));
	} else {
		$r = hexdec(substr($color, 0, 2));
		$g = hexdec(substr($color, 2, 2));
		$b = hexdec(substr($color, 4, 2));
	}

	$result = imagecreatetruecolor($imgSize->width + $size * 2, $imgSize->height + $size * 2);
	imagealphablending($result, false);

	for ($i = 0; $i < $size; $i++) {
		imagefilledrectangle(
			$result,
			$i, $i,
			$imgSize->width + $size * 2 - $i - 1, $imgSize->height + $size * 2 - $i - 1,
			imagecolorallocatealpha($result, $r, $g, $b, 127 - ceil(127 / $size * ($i + 1)))
		);	
	}
	imagealphablending($result, true);

	imagecopy($result, $img, $size, $size, 0, 0, $imgSize->width, $imgSize->height);
	imagealphablending($result, true);
	
	return $result;
}

function write($data, $nl = false)
{
	echo $data . ($nl ? '<br />' : '');
	flush();
	ob_flush();
}

function resetTimeout($timeout = null)
{
	if (is_null($timeout))
		$timeout = (int)ini_get('max_execution_time'); 
	set_time_limit($timeout);
}

function httpUrl()
{
	$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
	$url = preg_replace('/index\.php$/', '', $url);
	return $url;
}

if (!function_exists('json_last_error_msg')) {
	function json_last_error_msg()
	{
		switch (json_last_error()) {
			default:
				return;
		    case JSON_ERROR_DEPTH:
		        $error = 'Maximum stack depth exceeded';
		    break;
		    case JSON_ERROR_STATE_MISMATCH:
		        $error = 'Underflow or the modes mismatch';
		    break;
		    case JSON_ERROR_CTRL_CHAR:
		        $error = 'Unexpected control character found';
		    break;
		    case JSON_ERROR_SYNTAX:
		        $error = 'Syntax error, malformed JSON';
		    break;
		    case JSON_ERROR_UTF8:
		        $error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
			break;
		}
		throw new Exception($error);
	}
}

function toUtf8(&$data) {
	if (is_string($data))
		return $data = utf8_encode($data);
	if (is_array($data) or is_object($data)) {
		foreach ($data as &$value)
			toutf8($value);
		unset($value);
	}
}
?>
