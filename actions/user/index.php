<?

	$state = in_array(get($_GET, k('user')), array('login', 'logout', 'lost', 'registration', 'profil')) ? $_GET['user'] : 'login';
	
	$actionPage = 'states/' . $state . '.php';
	
	switch ($state) {
		case 'login' :
			
			if ($user)
				go('?album');
			
			if (exists($_POST, 'login') and exists($_POST, 'password'))
				$sg->userLogin($_POST['login'], $_POST['password']);
			
		break;
		case 'logout' :
			$sg->userLogout();
		break;
		case 'lost' :
			if ($user)
				go('?album');
				
			if ($login = get($_POST, k('login')))
				$sg->userPasswordLost($login);
				
			if ($code = get($_GET, k('pcode')))
				$sg->userPasswordReset($code, get($_POST, k('password')), get($_POST, k('password-check')));
					
				
		break;
		case 'registration' :
		
			if ($user)
				go('?album');
		
			if ($code = get($_GET, k('rcode')))
				$sg->userActive($code);
		
			if (exists($_POST, 'registration'))
				$sg->userRegistration($_POST['name'], $_POST['mail'], $_POST['login'], $_POST['password'], $_POST['password-check']);
		break;
		case 'profil' :
			if (!$user)
				go('?');
		
			if ($code = get($_GET, k('mcode')))
				$sg->userUpdateMail($code);
		
			if ($user and exists($_POST, 'profil'))
				$sg->userUpdate($_POST);
		break;
	}
?>
