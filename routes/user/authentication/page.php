<form class="simpleform" method="post" action="?user=authentication">
	<div class="simpleform-caption">Authentication</div>
	<p>
		<label for="email">Email :</label><br />
		<input type="email" id="email" name="email" value="<?=get($_POST, k('email'))?>" />
	</p>
	<p>
		<label for="password">Password :</label><br />
		<input type="password" id="password" name="password" />
	</p>
	<p class="simpleform-options">
		<label for="keep-connection">Keep connection</label>
		<label for="keep-connection" class="checkbox"><input type="checkbox" checked="checked" id="keep-connection" name="keep-connection" value="1" /></label>
	</p>
	<p class="simpleform-buttons">
		<input type="submit" value="Valid" />
	</p>
	<p class="simpleform-options">
		<a href="?user=lost-password">Lost password</a>
	</p>
	<? if (!$sg->parameters->isDisableRegistration()) : ?>
	<p class="simpleform-options">
		<a href="?user=registration">Registration</a>
	</p>
	<? endif; ?>
</form>
