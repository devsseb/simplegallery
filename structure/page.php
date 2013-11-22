<!DOCTYPE html>
<html>
	<head>
		<title><?=toHtml($sg->parameters->getGalleryName() . ($response->structure->title ? ' - ' . $response->structure->title : ''))?></title>
		<link rel="stylesheet" type="text/css" href="structure/style.css" />
<? if (is_file($file = 'routes/' . $response->route . '/' . $response->action . '/style.css')) : ?>
		<link rel="stylesheet" type="text/css" href="<?=toHtml($file)?>" />
<? endif; ?>
		<script type='text/javascript' src="structure/mootools.js"></script>
		<script>var locale = <?=json_encode($response->js)?>;</script>
		<script type='text/javascript' src="structure/script.js"></script>
<? if (is_file($file = 'routes/' . $response->route . '/' . $response->action . '/script.js')) : ?>
		<script type='text/javascript' src="<?=toHtml($file)?>"></script>
<? endif; ?>
	</head>
	<body>
		<header id="header">
		<? if (gete($sg, k('user'))) : ?>
			<input type="hidden" id="sgUser" value="<?=toHtml(json_encode(array(
				'name' => $sg->user->getName(),
				'admin' => $sg->user->isAdmin()
			)))?>" />
		<? endif; ?>	
		<? if ($response->structure->back->enable) : ?>
			<a title="<?=$sg->l('structure.back')?>" id="back" href="<?=toHtml($response->structure->back->url)?>">&lt;</a>
		<? endif; ?>
			<div id="title"><?=toHtml($response->structure->title ?: $sg->parameters->getGalleryName())?></div>
		<? if ($response->menu) : ?>
			<div id="menu">
				<div id="menu-button"><div></div><div></div><div></div></div>
				<ul id="menu-unroll">
			<? if ($response->menu->albumconfig->enable) : ?>
					<li><a title="<?=$sg->l('structure.menu.configure-album')?>" href="<?=toHtml($response->menu->albumconfig->url)?>"><?=$sg->l('structure.menu.configure-album')?></a></li>
			<? endif; ?>
			<? if ($response->menu->load->enable) : ?>
					<li><a title="<?=$sg->l('structure.menu.menu.manage-albums')?>" href="<?=toHtml($response->menu->load->url)?>"><?=$sg->l('structure.menu.manage-albums')?></a></li>
			<? endif; ?>
			<? if ($response->menu->loadAnalyze->enable) : ?>
					<li><a title="<?=$sg->l('structure.menu.analyze-selected-albums')?>" id="analyzer" href="<?=toHtml($response->menu->loadAnalyze->url)?>"><?=$sg->l('structure.menu.analyze-selected-albums')?></a></li>
			<? endif; ?>
			<? if ($response->menu->loadSynchronize->enable) : ?>
					<li><a title="<?=$sg->l('structure.menu.synchronize-albums-structure')?>" href="<?=toHtml($response->menu->loadSynchronize->url)?>"><?=$sg->l('structure.menu.synchronize-albums-structure')?></a></li>
			<? endif; ?>
			<? if ($response->menu->users->enable) : ?>
					<li><a title="<?=$sg->l('structure.menu.manage-users')?>" href="<?=toHtml($response->menu->users->url)?>"><?=$sg->l('structure.menu.manage-users')?></a></li>
			<? endif; ?>
			<? if ($response->menu->deleted->enable) : ?>
					<li><a title="<?=$sg->l('structure.menu.show-deleted-medias')?>" href="<?=toHtml($response->menu->deleted->url)?>" id="menu-deleted"><?=$sg->l('structure.menu.show-deleted-medias')?></a></li>
			<? endif; ?>
			<? if ($response->menu->parameters->enable) : ?>
					<li><a title="<?=$sg->l('structure.menu.parameters')?>" href="<?=toHtml($response->menu->parameters->url)?>"><?=$sg->l('structure.menu.parameters')?></a></li>
			<? endif; ?>
					<li><a title="<?=$sg->l('structure.menu.my-profile')?>" href="?user=profile"><?=$sg->l('structure.menu.my-profile')?></a></li>
					<li><a title="<?=$sg->l('structure.menu.logout')?>" href="?user=logout"><?=$sg->l('structure.menu.logout')?></a></li>
				</ul>
			</div>
		<? endif; ?>
		</header>
		<div id="content">
			<? if ($message = gete($_SESSION, k('messages', 'success'))) : ?>
				<div class="message message-success"><?=nl2br(toHtml($message))?></div>
				<?unset($_SESSION['messages']['success'])?>
			<? endif; ?>
			<? if ($message = gete($_SESSION, k('messages', 'error'))) : ?>
				<div class="message message-error"><?=nl2br(toHtml($message))?></div>
				<?unset($_SESSION['messages']['error'])?>
			<? endif; ?>
			<? if ($message = gete($_SESSION, k('messages', 'information'))) : ?>
				<div class="message message-information"><?=nl2br(toHtml($message))?></div>
				<?unset($_SESSION['messages']['information'])?>
			<? endif; ?>
			<?include 'routes/' . $response->route . '/' . $response->action . '/page.php'?>
		</div>
<? if ($config->debug) : ?>
		<div style="position:fixed;right:16px;bottom:0px;z-index:999;background-color:rgba(255,255,255,0.5);"><?=chronoGet('phptime')?></div>
<? endif; ?>
	</body>
</html>
