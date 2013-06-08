<?

	$state = in_array(get($_GET, k('user')), array('login', 'logout', 'lost', 'registration', 'profil')) ? $_GET['user'] : 'login';
	
	$actionPage = 'states/' . $state . '.php';
	
	switch ($state) {
		case 'login' :

			if (!get($sg->config, k('users')))
				go('?user=registration');
			
			if ($user)
				go('?album');
			
			if (exists($_POST, 'mail') and exists($_POST, 'password'))
				$sg->userLogin($_POST['mail'], $_POST['password']);
			
		break;
		case 'logout' :
			$sg->userLogout();
		break;
		case 'lost' :
			if ($user)
				go('?album');
				
			if ($mail = get($_POST, k('mail')))
				$sg->userPasswordLost($mail);
				
			if ($code = get($_GET, k('pcode')))
				$sg->userPasswordReset($code, get($_POST, k('password')), get($_POST, k('password-check')));
					
				
		break;
		case 'registration' :

			if (get($sg->config->parameters, k('registration-disable')) and get($sg->config, k('users')))
				go('?');

			if ($user)
				go('?album');

			if (exists($_POST, 'name'))
				$sg->userRegistration($_POST['name'], $_POST['mail'], $_POST['password'], $_POST['password-check']);

			if ($code = get($_GET, k('rcode')))
				$sg->userActive($code);
				
			if ($code = get($_POST, k('rcode')))
				$sg->userActive($code, $_POST['password'], $_POST['password-check']);
				
			$code = get($_GET, k('rpcode'));

		break;
		case 'profil' :
			if (!$user)
				go('?');
		
			if ($user and exists($_POST, 'profil'))
				$sg->userUpdate($_POST);
				
			if ($code = get($_GET, k('mcode')))
				$sg->userUpdateMail($code);
		break;
	}
?>
