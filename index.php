<?
try {

	$phpStart = explode(' ', microtime());
	session_start();
	
	$debug = true;
	include('./classes/functions.php');
	include('./classes/Get.class.php');
	new Get();
	include('./classes/Debug.class.php');
	new Debug();
	Debug::enable(true);

	if (!is_file('./config.php')) {

		$user = false;
		$action = 'install';
	
	} else {

		$config = new StdClass();
		include('./config.php');
		include('./classes/Simplegallery.class.php');
		include('./classes/Locale.class.php');

		$sg = new Simplegallery($config->privatePath);
		$user = $sg->getUser();

		$action = geta(array_keys($_GET), k('0'));
		$action = in_array($action, array('album', 'media', 'admin')) ? $action : 'user';

	}

		$actionPath = './actions/' . $action . '/';
		$actionPage = 'page.php';
		include($actionPath . 'index.php');

		include('./structure/page.php');

} catch (Exception $exception) {
	echo '<!DOCTYPE html><html><head><title>' . toHtml($sg->config->parameters->name) . '</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>';
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
