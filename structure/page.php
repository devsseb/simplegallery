<!DOCTYPE html>
<html>
	<head>
		<title><?=toHtml($sg->parameters->name)?></title>
 		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 		<link rel="stylesheet" type="text/css" href="./structure/style.css" />
<?	if (is_file($file = $actionPath . 'style.css')) : ?>
		<link rel="stylesheet" type="text/css" href="<?=$file?>" />
<? endif; ?>

<!--[if lt IE 9]>
<script>
document.createElement('header');
document.createElement('footer');
</script>
<![endif]-->

		<script type="text/javascript" src="./structure/mootools.js"></script>
<?	if (is_file($file = $actionPath . 'script.js')) : ?>
		<script type="text/javascript" src="<?=$file?>"></script>
<? endif; ?>

 	</head>
 	<body>
 		<header>
	 		<a href="?">
	 			<img class="simplegallery-logo" src="structure/images/simplegallery.png" />
	 			<h1><?=toHtml($sg->parameters->name)?></h1>
 			</a>
<? if (get($sg, k('user'))) : ?>
 			<div class="header-menu">
	<? if ($sg->user->admin) : ?>
				<a href="?admin"><?=l('admin._')?></a><br />
	<? endif; ?>
 				<?=l('structure.logged-in-as')?> <a href="?user=profil" title="Update my profil"><span class="header-login"><?=toHtml($sg->user->name)?></span></a> |
 				<a href="?user=logout"><?=l('structure.logout')?></a>
 			</div>
<? endif; ?>
 		</header>
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
<? endif; ?>
	 	<div class="page">
			<? include($actionPath . $actionPage); ?>
		</div>
		<footer>
			<?=l('structure.time-generation', round(chronoGet('phptime'), 3))?> |
			<a href="mailto:essarea@gmail.com">Ess</a> © 2012<?=date('Y') > 2012 ? ' - ' . date('Y') : ''?>
		</footer>
	</body>
</html>
