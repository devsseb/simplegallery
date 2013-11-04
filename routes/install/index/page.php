<h1>Installation</h1>
<form action="?install=index" method="post">
	<p>
		<label for="albumsPath">Medias path : </label><input id="albumsPath" name="albumsPath" value="<?=toHtml(get($response->data, k('config', 'albumsPath')))?>" />
	</p>
	<p>
		<label for="thumbsPath">Thumbnails path : </label><input id="thumbsPath" name="thumbsPath" value="<?=toHtml(get($response->data, k('config', 'thumbsPath')))?>" />
	</p>
	<p>
		<label for="database_dsn">Database DSN : </label>
		<span class="exemple">( mysql:dbname=my_database_name;charset=utf8 )</span>
		<input id="database_dsn" name="database_dsn" value="<?=toHtml(get($response->data, k('config', 'database', 'dsn')))?>" />
	</p>
	<p>
		<label for="database_user">Database user : </label>
		<input id="database_user" name="database_user" value="<?=toHtml(get($response->data, k('config', 'database', 'user')))?>" />
	</p>
	<p>
		<label for="database_password">Database password : </label>
		<input type="password" id="database_password" name="database_password" value="<?=toHtml(get($response->data, k('config', 'database', 'password')))?>" />
	</p>
	<p class="form-control">
		<input type="submit" value="install" />
	</p>
</form>
