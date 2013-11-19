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
		<header id="header">
		<? if ($response->structure->back->enable) : ?>
			<a title="Back" id="back" href="<?=toHtml($response->structure->back->url)?>">&lt;</a>
		<? endif; ?>
			<div id="title"><?=toHtml($response->structure->title ?: 'SimpleGallery')?></div>
		<? if ($response->menu) : ?>
			<div id="menu">
				<div id="menu-button"><div></div><div></div><div></div></div>
				<ul id="menu-unroll">
			<? if ($response->menu->albumconfig->enable) : ?>
					<li><a title="Configure album" href="<?=toHtml($response->menu->albumconfig->url)?>">Configure album</a></li>
			<? endif; ?>
			<? if ($response->menu->load->enable) : ?>
					<li><a title="Manage albums" href="<?=toHtml($response->menu->load->url)?>">Manage albums</a></li>
			<? endif; ?>
			<? if ($response->menu->loadAnalyze->enable) : ?>
					<li><a title="Analyze selected albums" id="analyzer" href="<?=toHtml($response->menu->loadAnalyze->url)?>">Analyze selected albums</a></li>
			<? endif; ?>
			<? if ($response->menu->loadSynchronize->enable) : ?>
					<li><a title="Synchronize albums structure" href="<?=toHtml($response->menu->loadSynchronize->url)?>">Synchronize albums structure</a></li>
			<? endif; ?>
			<? if ($response->menu->users->enable) : ?>
					<li><a title="User management" href="<?=toHtml($response->menu->users->url)?>">Manage users</a></li>
			<? endif; ?>
			<? if ($response->menu->deleted->enable) : ?>
					<li><a title="Show deleted medias" href="<?=toHtml($response->menu->deleted->url)?>" id="menu-deleted">Show deleted medias</a></li>
			<? endif; ?>
					<li><a title="Logout" href="?user=logout">Logout</a></li>
				</ul>
			</div>
		<? endif; ?>
		</header>
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
		<div style="position:fixed;right:16px;bottom:0px;z-index:999;background-color:rgba(255,255,255,0.5);"><?=chronoGet('phptime')?></div>
<? endif; ?>
	</body>
</html>
