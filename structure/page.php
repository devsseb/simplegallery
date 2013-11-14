<!DOCTYPE html>
<html>
	<head>
		<title>SG2</title>
		<link rel="stylesheet" type="text/css" href="structure/style.css" />
<? if (is_file($file = 'routes/' . $response->route . '/' . $response->action . '/style.css')) : ?>
		<link rel="stylesheet" type="text/css" href="<?=toHtml($file)?>" />
<? endif; ?>
		<script type='text/javascript' src="structure/mootools.js"></script>
		<script type='text/javascript' src="structure/script.js"></script>
<? if (is_file($file = 'routes/' . $response->route . '/' . $response->action . '/script.js')) : ?>
		<script type='text/javascript' src="<?=toHtml($file)?>"></script>
<? endif; ?>
	</head>
	<body>
	<? if ($response->menu) : ?>
		<div id="menu">
		<? if ($response->menu->back->enable) : ?>
			<a title="Back" class="menu-button menu-back" href="<?=toHtml($response->menu->back->url)?>"></a>
		<? endif; ?>
		<? if ($response->menu->albumconfig->enable) : ?>
			<a title="Album config" class="menu-button menu-albumconfig" href="<?=toHtml($response->menu->albumconfig->url)?>"></a>
		<? endif; ?>
		<? if ($response->menu->load->enable) : ?>
			<a title="Load" class="menu-button menu-load" href="<?=toHtml($response->menu->load->url)?>"></a>
		<? endif; ?>
		<? if ($response->menu->users->enable) : ?>
			<a title="User management" class="menu-button menu-users" href="<?=toHtml($response->menu->users->url)?>"></a>
		<? endif; ?>
		<? if ($response->menu->deleted->enable) : ?>
			<a title="Show deleted medias" class="menu-button menu-deleted" href="<?=toHtml($response->menu->deleted->url)?>" id="menu-deleted"></a>
		<? endif; ?>
			<a title="Logout" class="menu-button menu-logout" href="?user=logout"></a>
		</div>
	<? endif; ?>
		<div id="content">
			<? if ($message = gete($_SESSION, k('messages', 'success'))) : ?>
				<div class="message message-success"><?=toHtml($message)?></div>
				<?unset($_SESSION['messages']['success'])?>
			<? endif; ?>
			<? if ($message = gete($_SESSION, k('messages', 'error'))) : ?>
				<div class="message message-error"><?=toHtml($message)?></div>
				<?unset($_SESSION['messages']['error'])?>
			<? endif; ?>
			<? if ($message = gete($_SESSION, k('messages', 'information'))) : ?>
				<div class="message message-information"><?=toHtml($message)?></div>
				<?unset($_SESSION['messages']['information'])?>
			<? endif; ?>
			<?include 'routes/' . $response->route . '/' . $response->action . '/page.php'?>
		</div>
<? if ($config->debug) : ?>
		<div style="position:fixed;right:0px;bottom:0px;"><?=chronoGet('phptime')?></div>
<? endif; ?>
	</body>
</html>
