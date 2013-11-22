<form class="simpleform" method="post" action="?user=authentication">
	<div class="simpleform-caption"><?=$sg->l('user.authentication.title')?></div>
	<p>
		<label for="email"><?=$sg->l('user.email')?> :</label><br />
		<input type="email" id="email" name="email" value="<?=get($_POST, k('email'))?>" />
	</p>
	<p>
		<label for="password"><?=$sg->l('user.password')?> :</label><br />
		<input type="password" id="password" name="password" />
	</p>
	<p class="simpleform-options">
		<label for="keep-connection"><?=$sg->l('user.authentication.keep-connection')?></label>
		<label for="keep-connection" class="checkbox"><input type="checkbox" checked="checked" id="keep-connection" name="keep-connection" value="1" /></label>
	</p>
	<p class="simpleform-buttons">
		<input type="submit" value="<?=$sg->l('valid')?>" />
	</p>
	<p class="simpleform-options">
		<a href="?user=lost-password"><?=$sg->l('user.authentication.lost-password')?></a>
	</p>
	<? if (!$sg->parameters->isDisableRegistration()) : ?>
	<p class="simpleform-options">
		<a href="?user=registration"><?=$sg->l('user.authentication.registration')?></a>
	</p>
	<? endif; ?>
</form>
