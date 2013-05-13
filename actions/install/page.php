<form class="install" action="?install" method="post">
	<h2 class="install-title">Install</h2>
	<div class="install-fields">
		<div class="install-field-info">Path to the private folder. This folder will contain your albums, thumbnails and configuration. It is highly recommended to place it in an inaccessible location.</div>
		<label for="install-private-path">Private path : </label><input type="text" id="install-private-path" name="privatePath" value="<?=toHtml(get($path))?>" />
	</div>
	<div class="install-submit"><input type="submit" value="Apply" /></div>
</form>
