<?
	if (!$user)
		error(l('access-error'), '?');

	$sg->loadAlbums();

	if ($id = get($_POST, k('id')))
		$sg->albumUpdate($id, $_POST);

	if ($id = get($_GET, k('download')))
		$sg->albumDownload($id);
	
	if ($id = get($_GET, k('id'))) {
		if ($sg->user->admin) {
			
			if (exists($_GET, 'generate'))
				$sg->albumGenerate($id);
		
			$check = $sg->albumCheck($id);
			$album = $check->album;

			if ($check->update)
				error(l('album.message.generate-update') . ' <a href="?album&generate&id=' . toHtml($album->id) . '">' . l('album.message.generate-update-link') . '</a>');
			elseif ($check->delete)
				information(l('album.message.generate-delete') . ' <a href="?album&generate&id=' . toHtml($album->id) . '">' . l('album.message.generate-delete-link') . '</a>');

		} else
			$album = $sg->getAlbum($id);

		foreach($album->medias as $media) {
			$media->styles = '';
			
			$flip = explode(' ', get($media, k('data', 'flip'), ''));
			$flip = (object)array(
				'h' => in_array('horizontal', $flip),
				'v' => in_array('vertical', $flip)
			);

			$rotation = get($media, k('data', 'rotation'), 0);

			switch (get($media, k('data', 'orientation'), 1)) {
				default :
					$rotation+= 0;
					$flip->h = $flip->h ? -1 : 1;
					$flip->v = $flip->v ? -1 : 1;
				break;
				case 2 :
					$rotation+= 0;
					$flip->h = $flip->h ? 1 : -1;
					$flip->v = $flip->v ? -1 : 1;
				break;
				case 3 :
					$rotation+= 180;
					$flip->h = $flip->h ? -1 : 1;
					$flip->v = $flip->v ? -1 : 1;
				break;
				case 4 :
					$rotation+= 0;
					$flip->h = $flip->h ? -1 : 1;
					$flip->v = $flip->v ? 1 : -1;
				break;
				case 5 :
					$rotation+= 90;
					$flip->h = $flip->h ? -1 : 1;
					$flip->v = $flip->v ? 1 : -1;
				break;
				case 6 :
					$rotation+= 90;
					$flip->h = $flip->h ? -1 : 1;
					$flip->v = $flip->v ? -1 : 1;
				break;
				case 7 :
					$rotation+= 270;
					$flip->h = $flip->h ? -1 : 1;
					$flip->v = $flip->v ? 1 : -1;
				break;
				case 8 :
					$rotation+= 270;
					$flip->h = $flip->h ? -1 : 1;
					$flip->v = $flip->v ? -1 : 1;
				break;
			}
			foreach (array('', '-webkit-', '-moz-', '-o-', '-ms-') as $cssPrefix)
				$media->styles.= $cssPrefix . 'transform:rotate(' . $rotation . 'deg) scaleX(' . $flip->h . ') scaleY(' . $flip->v . ');';
		}

	}

?>
