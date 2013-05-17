<?
	if (!$user)
		error(l('access-error'), '?');
		
	if (!in_array('admins', get($sg->user, k('groups'), array())))
		error(l('access-error'), '?');
	
	if (exists($_POST, 'admin'))
		$sg->adminUpdate($_POST);
		
	if (exists($_GET, 'userDelete'))
		$sg->userDelete($_GET['userDelete']);
		
?>
