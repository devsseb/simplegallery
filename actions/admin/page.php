<div id="admin-locales"><?=json_encode(array('user-delete-confirm' => l('admin.user-delete-confirm')))?></div>
<form class="admin" action="?admin" method="post">
	<h2 class="admin-title"><?=l('admin._')?></h2>
	<input type="hidden" name="admin" value="" />
	<div class="admin-parameters">
		<label for="parameters-name"><?=l('admin.parameters.name')?> : </label><input type="text" id="parameters-name" name="name" value="<?=toHtml(get($sg->config->parameters, k('name'), 'SimpleGallery'))?>" /><br />
		<label for="parameters-locale"><?=l('admin.parameters.locale')?> : </label><select id="parameters-locale" name="locale">
<? foreach ($sg->locale->langs as $lang) : ?>
			<option value="<?=toHtml($lang)?>"<?=get($sg->config->parameters, k('locale')) == $lang ? ' selected="selected"' : ''?>><?=toHtml($lang)?></option>
<? endforeach; ?>
		</select><br />
		<label for="parameters-registration-disable"><?=l('admin.parameters.registration-disable')?> : </label>
		<input type="checkbox" id="parameters-registration-disable" name="registration-disable" value="1"<?=get($sg->config->parameters, k('registration-disable')) ? ' checked="checked"' : ''?> />
	</div>
	<label><?=l('admin.users')?> :</label>
	<table class="admin-users-groups">
		<tr>
			<th class="table-empty-cell-top-left"></th>
<? foreach ($sg->config->groups as $group) : ?>
			<th><?=toHtml($group)?></th>
<? endforeach; ?>
			<th class="table-empty-cell-top-right"></th>
		</tr>
<? foreach ($sg->config->users as $user) : ?>
		<tr>
			<th<?=$user->active !== true ? ' class="admin-user-no-active" title="' . l('admin.user-no-active') . '"' : ''?>><?=toHtml($user->login)?></th>
	<? foreach ($sg->config->groups as $group) : ?>
			<td class="admin-users-group-check"><input type="checkbox" value="1" name="usersGroups[<?=toHtml($user->login)?>][<?=toHtml($group)?>]" <?=in_array($group, get($user, k('groups'), array())) ? 'checked="checked" ' : ''?>/></td>
	<? endforeach; ?>
			<td class="admin-user-delete"><a href="?admin&userDelete=<?=toHtml($user->login)?>"><?=l('admin.user-delete')?></a></td>
<? endforeach; ?>
	</table>
	<p>
		<label for="admin-groups"><?=l('admin.groups')?> :</label><br />
		<textarea id="admin-groups" name="groups" cols="30" rows="<?=count($sg->config->groups)+2?>"><?=implode(chr(10), $sg->config->groups)?></textarea>
	</p>
	<div class="admin-submit"><input type="submit" value="<?=l('apply')?>" /></div>
	<div class="admin-links">
		<a href="?"><?=l('cancel')?></a>
	</div>
</form>
