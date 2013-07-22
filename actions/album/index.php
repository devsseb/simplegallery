<?

	if (!$sg->user)
		error(l('access-error'), '?');

	// Album download
	if ($id = get($_GET, k('download')))
		$sg->albumDownload($id);
	
	if ($id = get($_GET, k('id'))) {

		// Album update
		if ($sg->user->admin and $_GET['album'] == 'update')
			$sg->albumUpdate($id, $_POST);

		$admin = ($_GET['album'] == 'admin' and $sg->user->admin);
		
		// Retrieve album $id
		$album = $sg->getAlbum($id);

		$comments_disable = ($sg->parameters->albums_comments_disable or $album->comments_disable);
		$medias_dates_disable = ($sg->parameters->albums_medias_dates_disable or $album->medias_dates_disable);
		$tags_disable = ($sg->parameters->albums_tags_disable or $album->tags_disable);

		// Prepare css transformation for medias
		$noThumb = true;
		foreach($album->medias as $media) {
			if ($media->md5)
				$noThumb = false;
		
			$media->styles = '';
			$flip = object(
				'h', (bool)$media->flip_horizontal,
				'v', (bool)$media->flip_vertical
			);

			$rotation = $media->rotation;

			switch ($media->orientation) {
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
			foreach (array('', '-webkit-', '-moz-', '-o-', '-ms-', 'ms') as $cssPrefix)
				$media->styles.= $cssPrefix . 'transform:rotate(' . $rotation . 'deg) scaleX(' . $flip->h . ') scaleY(' . $flip->v . ');';
/*
			foreach ($media->data->comments as $comment)
				$comment->user = $comment->user == $sg->user->mail ? l('album.media.me') : get($sg->config->users, k($comment->user, 'name'));
*/
		}
		
	}
	
	if (!$sg->parameters->albums_calendar_disable) {
	
		// Prepare dates albums for calendar
		$dates = array();
		foreach ($sg->albums as $albumDate) {
			$date = object('start', $albumDate->date_start, 'end', $albumDate->date_end);
			
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

	}
	
	function dates_add(&$dates, $date, $album)
	{
		if (!exists($dates, $date))
			$dates[$date] = array();
		$dates[$date][] = object(
			'id', $album->id,
			'name', get($album->data, k('name'), $album->name)
		);
	}

?>
