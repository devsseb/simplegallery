<h1><?=sg->l('install.index.title')?></h1>
<form action="?install=index" method="post">
	<p>
		<label for="albumsPath"><?=sg->l('install.index.medias-path')?> : </label><input id="albumsPath" name="albumsPath" value="<?=toHtml(get($response->data, k('config', 'albumsPath')))?>" />
	</p>
	<p>
		<label for="thumbsPath"><?=sg->l('install.index.thumbnails-path')?> : </label><input id="thumbsPath" name="thumbsPath" value="<?=toHtml(get($response->data, k('config', 'thumbsPath')))?>" />
	</p>
	<p>
		<label for="database_dsn"><?=sg->l('install.index.database-dsn')?> : </label>
		<span class="exemple">( mysql:dbname=my_database_name;charset=utf8 )</span>
		<input id="database_dsn" name="database_dsn" value="<?=toHtml(get($response->data, k('config', 'database', 'dsn')))?>" />
	</p>
	<p>
		<label for="database_user"><?=sg->l('install.index.database-user')?> : </label>
		<input id="database_user" name="database_user" value="<?=toHtml(get($response->data, k('config', 'database', 'user')))?>" />
	</p>
	<p>
		<label for="database_password"><?=sg->l('install.index.database-passord')?> : </label>
		<input type="password" id="database_password" name="database_password" value="<?=toHtml(get($response->data, k('config', 'database', 'password')))?>" />
	</p>
	<p class="form-control">
		<input type="submit" value="<?=sg->l('install.index.install')?>" />
	</p>
</form>
