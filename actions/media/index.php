<?
	$albumId = get($_GET, k('album'));
	$media = get($_GET, k('media'));
	$dim = get($_GET, k('dim'));

	if ($update = get($_GET, k('update')))
		$sg->mediaUpdate($albumId, $media, $update);
	
	$album = $sg->getMedia($albumId, $media, $dim);
?>
