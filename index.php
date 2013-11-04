<?
try {

	session_start();
	
	include 'includes/functions.php';
	include 'includes/Get.php';
	include 'includes/Object.php';
	
	$config = object();

	if (is_file('config.php'))
		include 'config.php';
	
	include 'includes/Debug.class.php';
	new Debug(get($config, k('debug'), false));

	chronoStart('phptime');

//	include('includes/Simplegallery.class.php');
	include('includes/sg/Simplegallery.class.php');
	include('includes/Book.class.php');
	$sg = new SimpleGallery($config);
	
	$response = $sg->routing();

	include 'structure/page.php';
	
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

