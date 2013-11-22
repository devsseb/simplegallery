<form class="simpleform" method="post" action="?user=registration">
	<div class="simpleform-caption"><?=$sg->l('user.registration.title')?></div>
	<p>
		<label for="email"><?=$sg->l('user.email')?> :</label><br />
		<input type="email" id="email" name="email" value="<?=toHtml(get($_POST, k('email')))?>" />
	</p>
	<p>
		<label for="name"><?=$sg->l('user.name')?> :</label><br />
		<input type="text" id="name" name="name" value="<?=toHtml(get($_POST, k('name')))?>" />
	</p>
	<p>
		<label for="password"><?=$sg->l('user.password')?> :</label><br />
		<input type="password" id="password" name="password" />
	</p>
	<p>
		<label for="password-verification"><?=$sg->l('user.password-verification')?> :</label><br />
		<input type="password" id="password-verification" name="password-verification" />
	</p>
	<p class="simpleform-buttons">
		<input type="submit" value="<?=$sg->l('valid')?>" />
	</p>
</form>
