<form class="install" action="?install" method="post">
	<h2 class="install-title"><?=l('install._')?></h2>
	<div class="install-fields">
		<div class="install-field-info"><?=l('install.informations')?></div>
		<label for="install-private-path"><?=l('install.private-path')?> : </label><input type="text" id="install-private-path" name="privatePath" value="<?=toHtml(get($path))?>" />
	</div>
	<div class="install-submit"><input type="submit" value="<?=l('apply')?>" /></div>
</form>
