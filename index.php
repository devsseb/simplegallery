<?
try {

	session_start();
	
	define('SG_VERSION', '0.1');
	
	include('./classes/functions.php');
	include('./classes/Get.class.php');
	new Get();
	include('./classes/Debug.class.php');
	new Debug();

	chronoStart('phptime');

	include('./classes/Locale.class.php');
	new Locale();
	include('./classes/Simplegallery.class.php');

	$user = false;

	if (is_file('./config.php')) {

		$config = new StdClass();
		include('./config.php');
		Debug::enable(get($config, k('debug')));		

		$action = geta(array_keys($_GET), k('0'));
		$action = in_array($action, array('album', 'media', 'admin')) ? $action : 'user';
	
	} else {

		$action = 'install';

	}

	$sg = new Simplegallery(get($config, k('privatePath')));

	$actionPath = './actions/' . $action . '/';
	$actionPage = 'page.php';
	include($actionPath . 'index.php');

	include('./structure/page.php');

} catch (Exception $exception) {
	echo '<!DOCTYPE html><html><head><title>' . toHtml(get($sg, k('parameters', 'name'), 'SimpleGallery')) . '</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>';
	echo '<br/>Une erreur est survenue.<br/>';
	echo '<br>';
	echo '<strong>Message :</strong>' . toHtml($exception->getMessage()) . '<br/>';
	echo '<strong>Fichier :</strong>' . toHtml($exception->getFile()) . '<br/>';
	echo '<strong>Ligne :</strong>' . toHtml($exception->getLine()) . '<br/>';
	echo '<strong>Pile :</strong>';
	echo '<br/>' . nl2br(toHtml($exception->getTraceAsString()));
	echo '<br/><br/><a href="./" title="Retour">Retour</a>';
	echo '</body></html>';
}
?>
