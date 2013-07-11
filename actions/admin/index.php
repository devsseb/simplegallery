<?
	if (!get($sg, k('user', 'admin')))
		error(l('access-error'), '?');
	
	switch ($state = get($_GET, k('admin'))) {
		default :
			$state = 'general';
			
			if (exists($_POST, 'name'))
				$sg->adminUpdate($state, $_POST);

			if (exists($_GET, 'userDelete'))
				$sg->userDelete($_GET['userDelete']);
		
		break;
		case 'albums' :

			if (exists($_POST, 'albums'))
				$sg->adminUpdate('albums', $_POST);

			if (exists($_GET, 'albumsReload'))
				$sg->loadAlbums();
		
		break;
		case 'check' :

			$sg->mediasGenerate($_GET['id']);
			exit();

		break;
	}

	$actionPage = 'states/' . $state . '.php';
?>
