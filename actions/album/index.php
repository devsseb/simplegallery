<?
	if (!$user)
		error(l('access-error'), '?');

	// Loads all albums in $sg
	$sg->loadAlbums();

	// Album update
	if ($id = get($_POST, k('id')))
		$sg->albumUpdate($id, $_POST);

	// Album download
	if ($id = get($_GET, k('download')))
		$sg->albumDownload($id);
	
	if ($id = get($_GET, k('id'))) {

		if ($admin = ($_GET['album'] == 'admin' and $sg->user->admin)) {
			
			// data.json regeneration
			if (exists($_GET, 'generate-data'))
				$sg->albumGenerateData($id);
			
			// Thumbs generation
			if (exists($_GET, 'generate'))
				$sg->albumGenerate($id);
		
			// Check thumbs
			$check = $sg->albumCheck($id);
			$album = $check->album;
			if ($check->update)
				error(l('album.message.generate-update') . ' <a href="?album&generate&id=' . toHtml($album->id) . '">' . l('album.message.generate-update-link') . '</a>');
			elseif ($check->delete)
				information(l('album.message.generate-delete') . ' <a href="?album&generate&id=' . toHtml($album->id) . '">' . l('album.message.generate-delete-link') . '</a>');

		} else
		
			// Retrieve album $id
			$album = $sg->getAlbum($id);

		// Prepare css transformation for medias
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
	
	if (!get($sg->config->parameters, k('albums-calendar-disable'))) {
	
		// Prepare dates albums for calendar
		$dates = array();
		foreach ($sg->albums as $albumDate) {
			if (!$date = get($albumDate->data, k('date')))
				continue;
			
			if ($date->start and $date->end) {
				$start = new DateTime($date->start);
				while ($start->format('Y-m-d') <= $date->end) {
					dates_add($dates, $start->format('Y-m-d'), $albumDate);
					$start->add(new DateInterval('P1D'));
				}
			} elseif ($date->start)
				dates_add($dates, $date->start, $albumDate);
			elseif ($date->end)
				dates_add($dates, $date->end, $albumDate);
		
		}
		$dates = json_encode($dates);

		function dates_add(&$dates, $date, $album)
		{
			if (!exists($dates, $date))
				$dates[$date] = array();
			$dates[$date][] = object(
				'id', $album->id,
				'name', get($album->data, k('name'), $album->name)
			);
		}
	}

?>
