<?

	if (!is_file('./config.php')) {
	
		if (exists($_POST, 'privatePath'))
			new Simplegallery($_POST['privatePath']);

	
	}
?>
