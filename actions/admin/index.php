<?
	if (!$user)
		error('Access is forbidden', '?');
		
	if (!in_array('admins', get($sg->user, k('groups'), array())))
		error('Access is forbidden', '?');
	
	if (exists($_POST, 'admin'))
		$sg->adminUpdate($_POST);
?>
