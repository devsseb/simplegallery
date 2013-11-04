<h1>Lost password</h1>
<div class="form-container">
	<form method="post" action="?user=lost-password">
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
			<label for="mail">Mail :</label><br />
			<input type="mail" id="mail" name="email" value="<?=get($_POST, k('email'))?>" />
		</p>
	<? endif; ?>
		<p class="form-control">
			<input type="submit" value="Valider" />
		</p>
		<p>
			<a href="?" class="form-link-right">Cancel</a>
		</p>
	</form>
</div>
