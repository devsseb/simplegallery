<?
	if (!$user)
		error(l('access-error'), '?');

	$sg->loadAlbums();

	if ($id = get($_POST, k('id')))
		$sg->albumUpdate($id, $_POST);

	if ($id = get($_GET, k('download')))
		$sg->albumDownload($id);
	
	if ($id = get($_GET, k('id')))
		$album = $sg->getAlbum($id);

?>
