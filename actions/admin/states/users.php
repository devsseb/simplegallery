<div id="admin-locales"><?=json_encode(array('user-delete-confirm' => l('admin.user-delete-confirm')))?></div>
<ul class="admin-states">
	<li><a href="?admin=general"><?=l('admin.state.general')?></a></li>
	<li><a href="?admin=albums"><?=l('admin.state.albums')?></a></li>
	<li><?=l('admin.state.users')?></li>
</ul>
<form class="box admin" action="?admin=users" method="post">
	<h2><?=l('admin._')?></h2>
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
			<th class="admin-user-admin"><?=l('admin.administrator')?></th>
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
			<td class="admin-users-group-check"><input type="checkbox" value="1"<?=$user->id == $sg->user->id ? ' disabled="disabled"' : ''?> name="usersAdmins[<?=toHtml($user->id)?>]" <?=$user->admin ? 'checked="checked" ' : ''?>/></td>
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
