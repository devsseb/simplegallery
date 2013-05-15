<form class="admin" action="?admin" method="post">
	<h2 class="admin-title"><?=l('admin._')?></h2>
	<input type="hidden" name="admin" value="" />
	<div class="admin-parameters">
		<label for="parameters-name"><?=l('admin.parameters.name')?> : </label><input type="text" id="parameters-name" name="name" value="<?=toHtml(get($sg->config->parameters, k('name'), 'SimpleGallery'))?>" /><br />
		<label for="parameters-locale"><?=l('admin.parameters.locale')?> : </label><select id="parameters-locale" name="locale">
<? foreach ($sg->locale->langs as $lang) : ?>
			<option value="<?=toHtml($lang)?>"<?=get($sg->config->parameters, k('locale')) == $lang ? ' selected="selected"' : ''?>><?=toHtml($lang)?></option>
<? endforeach; ?>
		</select>
	</div>
	<label><?=l('admin.users-groups')?> :</label>
	<table class="admin-users-groups">
		<tr>
			<th></th>
<? foreach ($sg->config->groups as $group) : ?>
			<th><?=toHtml($group)?></th>
<? endforeach; ?>
		</tr>
<? foreach ($sg->config->users as $user) : ?>
		<tr>
			<th><?=toHtml($user->login)?></th>
	<? foreach ($sg->config->groups as $group) : ?>
			<td class="admin-users-group-check"><input type="checkbox" value="1" name="usersGroups[<?=toHtml($user->login)?>][<?=toHtml($group)?>]" <?=in_array($group, get($user, k('groups'), array())) ? 'checked="checked" ' : ''?>/></td>
	<? endforeach; ?>
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
