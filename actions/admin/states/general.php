<div id="admin-locales"><?=json_encode(array('user-delete-confirm' => l('admin.user-delete-confirm')))?></div>
<ul class="admin-states">
	<li><?=l('admin.state.general')?></li>
	<li><a href="?admin=albums"><?=l('admin.state.albums')?></a></li>
</ul>
<form class="box admin" action="?admin=general" method="post">
	<h2><?=l('admin._')?></h2>
	<div class="admin-parameters">
		<label for="parameters-name"><?=l('admin.parameters.name')?> : </label><input type="text" id="parameters-name" name="name" value="<?=toHtml($sg->parameters->name)?>" /><br />
		<label for="parameters-locale"><?=l('admin.parameters.locale')?> : </label><select id="parameters-locale" name="locale">
			<option value=""<?=!$sg->parameters->locale ? ' selected="selected"' : ''?>><?=l('admin.parameters.locale-auto')?></option>
<? foreach ($sg->locale->langs as $lang) : ?>
			<option value="<?=toHtml($lang)?>"<?=$sg->parameters->locale == $lang ? ' selected="selected"' : ''?>><?=toHtml($lang)?></option>
<? endforeach; ?>
		</select><br />
		<label for="parameters-registration-disable"><?=l('admin.parameters.registration-disable')?> : </label>
		<input type="checkbox" id="parameters-registration-disable" name="registration-disable" value="1"<?=$sg->parameters->registration_disable ? ' checked="checked"' : ''?> /><br />
		<label for="parameters-albums-calendar-disable"><?=l('admin.parameters.albums-calendar-disable')?> : </label>
		<input type="checkbox" id="parameters-albums-calendar-disable" name="albums-calendar-disable" value="1"<?=$sg->parameters->albums_calendar_disable ? ' checked="checked"' : ''?> /><br />
		<label for="parameters-albums-comments-disable"><?=l('admin.parameters.albums-comments-disable')?> : </label>
		<input type="checkbox" id="parameters-albums-comments-disable" name="albums-comments-disable" value="1"<?=$sg->parameters->albums_comments_disable ? ' checked="checked"' : ''?> /><br />
		<label for="parameters-albums-medias-dates-disable"><?=l('admin.parameters.albums-medias-dates-disable')?> : </label>
		<input type="checkbox" id="parameters-albums-medias-dates-disable" name="albums-medias-dates-disable" value="1"<?=$sg->parameters->albums_medias_dates_disable ? ' checked="checked"' : ''?> /><?/*<br />
		<label for="parameters-albums-tags-disable"><?=l('admin.parameters.albums-tags-disable')?> : </label>
		<input type="checkbox" id="parameters-albums-tags-disable" name="albums-tags-disable" value="1"<?=$sg->parameters->albums_tags_disable ? ' checked="checked"' : ''?> />*/?>
	</div>
	<label><?=l('admin.users')?> <span class="admin-user-add">(<a href="#" id="user-add-link"><?=l('admin.user-add-link')?></a>)</a>:</label>
	<div id="user-add">
		<table>
			<tr><th><?=l('user.profil.name')?> :</th><td class="user-field"><input name="user-name" type="text" /></td></tr>
			<tr><th><?=l('user.profil.mail')?> :</th><td class="user-field"><input name="user-mail" type="email" /></td></tr>
			<tr><td class="box-submit" colspan="2"><input type="submit" value="<?=l('admin.user-add')?>" /></td></tr>
		</table>
	</div>
	<table class="admin-users-groups">
		<tr>
			<th class="table-empty-cell-top-left1"></th>
			<th class="table-empty-cell-top-left1"></th>
			<th class="table-empty-cell-top-left2"></th>
<? foreach ($sg->getGroups() as $group) : ?>
			<th><?=toHtml($group->name)?></th>
<? endforeach; ?>
			<th class="table-empty-cell-top-right"></th>
		</tr>
<? foreach ($sg->getUsers() as $user) : ?>
		<tr>
			<th class="admin-user-name<?=$user->active !== '1' ? ' admin-user-no-active" title="' . l('admin.user-no-active') : ''?>"><?=toHtml($user->id)?></th>
			<th class="admin-user-name<?=$user->active !== '1' ? ' admin-user-no-active" title="' . l('admin.user-no-active') : ''?>"><?=toHtml($user->name)?></th>
			<th class="admin-user-name<?=$user->active !== '1' ? ' admin-user-no-active" title="' . l('admin.user-no-active') : ''?>"><?=toHtml($user->mail)?></th>
	<? foreach ($sg->getGroups() as $group) : ?>
			<td class="admin-users-group-check"><input type="checkbox" value="1" name="usersGroups[<?=toHtml($user->id)?>][<?=toHtml($group->id)?>]" <?=exists($user->groups, $group->id) ? 'checked="checked" ' : ''?>/></td>
	<? endforeach; ?>
			<td class="admin-user-delete"><a href="?admin&userDelete=<?=toHtml($user->id)?>"><?=l('admin.user-delete')?></a></td>
<? endforeach; ?>
	</table>
	<p>
		<label><?=l('admin.groups')?> :</label>
		<ul id="adminGroups">
		<? foreach ($sg->getGroups() as $group) : ?>
			<li><input type="text" name="groups[<?=toHtml($group->id)?>]" value="<?=toHtml($group->name)?>" /></li>
		<? endforeach; ?>
		</ul>
		<a href="#" id="adminGroupAdd"><?=l('admin.group-add')?></a>
	</p>
	<div class="box-submit"><input type="submit" value="<?=l('apply')?>" /></div>
	<div class="box-links">
		<a href="?"><?=l('cancel')?></a>
	</div>
</form>
