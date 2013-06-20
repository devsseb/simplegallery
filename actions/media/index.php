<?
	$albumId = get($_GET, k('album'));
	$media = get($_GET, k('media'));
	$dim = get($_GET, k('dim'));

	if (exists($_GET, 'download'))
		$sg->mediaDownload($albumId, $media);

	if ($sg->user->admin and $update = get($_POST, k('update')))
		$sg->mediaUpdate($albumId, $media, $update);
	
	$album = $sg->getMedia($albumId, $media, $dim);
?>
