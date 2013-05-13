<?
	if (!$user)
		error('Access is forbidden', '?');

	$sg->loadAlbums();

	if ($id = get($_POST, k('id'))) {
		
		$album = $sg->getAlbum($id);
		$sg->albumUpdate($album->id, $_POST);

		success('The access of the album have been updated successfully', '?album&id=' . $album->id);
		
	}

	if ($id = get($_GET, k('download')))
		$sg->albumDownload($id);
	
	if ($id = get($_GET, k('id')))
		$album = $sg->getAlbum($id);

?>
