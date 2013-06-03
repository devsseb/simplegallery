<!DOCTYPE html>
<html>
	<head>
		<title><?=toHtml(get($sg, k('config', 'parameters', 'name'), 'SimpleGallery'))?></title>
 		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 		<link rel="stylesheet" type="text/css" href="./structure/style.css" />
		<script type="text/javascript" src="./structure/mootools.js"></script>

<?	if (is_file($file = $actionPath . 'style.css')) : ?>
		<link rel="stylesheet" type="text/css" href="<?=$file?>" />
<? endif; ?>
<?	if (is_file($file = $actionPath . 'script.js')) : ?>
		<script type="text/javascript" src="<?=$file?>"></script>
<? endif; ?>

 	</head>
 	<body>
 		<header>
 			<h1><a href="?"><?=toHtml(get($sg, k('config', 'parameters', 'name'), 'SimpleGallery'))?></a></h1>
<? if ($user) : ?>
 			<div class="header-menu">
	<? if (in_array('admins', $sg->user->groups)) : ?>
				<a href="?admin"><?=l('admin._')?></a><br />
	<? endif; ?>
 				<?=l('structure.logged-in-as')?> <a href="?user=profil" title="Update my profil"><span class="header-login"><?=toHtml($sg->user->name)?></span></a> |
 				<a href="?user=logout"><?=l('structure.logout')?></a>
 			</div>
<? endif; ?>
 		</header>
	 	<div class="page">
<? if ($message = get($_SESSION, k('messages' ,'success'))) : ?>
			<div class="message_success"><?=$message?></div>
	<? unset($_SESSION['messages']['success'])?>
<? endif;
	if ($message = get($_SESSION, k('messages' ,'error'))) : ?>
			<div class="message_error"><?=$message?></div>
	<? unset($_SESSION['messages']['error'])?>
<? endif;
	if ($message = get($_SESSION, k('messages' ,'information'))) : ?>
			<div class="message_information"><?=$message?></div>
	<? unset($_SESSION['messages']['information'])?>
<? endif;
		include($actionPath . $actionPage);
?>
		</div>
		<footer>
			<?=l('structure.time-generation', round(chronoGet('phptime'), 3))?> |
			<a href="mailto:essarea@gmail.com">Ess</a> © 2012<?=date('Y') > 2012 ? ' - ' . date('Y') : ''?>
		</footer>
	</body>
</html>
