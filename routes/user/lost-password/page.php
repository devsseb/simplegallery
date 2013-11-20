<form class="simpleform" method="post" action="?user=lost-password">
	<div class="simpleform-caption">Lost password</div>
<? if (get($response->data, k('update'))) : ?>
	<p>
		<input type="hidden" name="pcode" value="<?=toHtml($response->data['update'])?>" ?>
		<label for="password">Password :</label><br />
		<input type="password" id="password" name="password" />
	</p>
	<p>
		<label for="password-verification">Password verification :</label><br />
		<input type="password" id="password-verification" name="password-verification" />
	</p>
<? else : ?>
	<p>
		<label for="email">Email :</label><br />
		<input type="email" id="email" name="email" value="<?=get($_POST, k('email'))?>" />
	</p>
<? endif; ?>
	<p class="simpleform-buttons">
		<input type="submit" value="Valid" />
	</p>
</form>
