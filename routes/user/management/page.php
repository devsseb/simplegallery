<form class="simpleform" method="post" action="?user=management">
	<div class="simpleform-caption"><?=$sg->l('user.management.groups.title')?></div>
	<p>
		<label for="group"><?=$sg->l('user.management.groups.add')?> : </label><br />
		<input type="text" id="group" name="group" value="<?=toHtml(get($_POST, k('group')))?>" />
	</p>
	<p class="simpleform-buttons">
		<input type="submit" value="<?=$sg->l('add')?>" />
	</p>
</form>
<ul class="groups">
<? foreach ($response->data['groups'] as $group) : ?>
	<li><?=toHtml($group->getName())?><sup><a title="<?=$sg->l('user.management.groups.delete')?>" href="?user=management&groupdelete=<?=toHtml($group->getId())?>" onclick="return confirm('<?=$sg->l('confirm-delete')?>')">X</a></sup></li>
<? endforeach; ?>
</ul>
<form class="simpleform" method="post" action="?user=management">
	<div class="simpleform-caption"><?=$sg->l('user.management.user-add.title')?></div>
	<p>
		<label for="name"><?=$sg->l('user.name')?> : </label><br />
		<input type="text" id="name" name="name" value="<?=toHtml(get($_POST, k('name')))?>" />
	</p>
	<p>
		<label for="email"><?=$sg->l('user.email')?> : </label><br />
		<input type="text" id="email" name="email" value="<?=toHtml(get($_POST, k('email')))?>" />
	</p>
	<p class="simpleform-buttons">
		<input type="submit" value="<?=$sg->l('add')?>" />
	</p>
</form>
<form class="simpleform user-form" method="post" action="?user=management">
	<div class="simpleform-caption"><?=$sg->l('user.management.users.title')?></div>
	<table class="users">
		<tr><th><?=$sg->l('user.name')?></th><th><?=$sg->l('user.email')?></th><th><?=$sg->l('user.management.users.is-admin')?></th><th><?=$sg->l('user.management.users.groups')?></th></tr>
	<? foreach ($response->data['users'] as $user) : ?>
		<tr>
			<td>
				<?=toHtml($user->getName())?><?=$user->isActive() ? '' : ' (no active)'?>
				<sup><a title="<?=$sg->l('user.management.users.delete')?>" href="?user=management&userdelete=<?=toHtml($user->getId())?>" onclick="return confirm('<?=$sg->l('confirm-delete')?>')">X</a></sup>
			</td>
			<td><?=toHtml($user->getEmail())?></td>
			<td class="text-center"><input type="checkbox" name="user[<?=toHtml($user->getId())?>][admin]" value="1"<?=$user->isAdmin() ? ' checked="checked"' : ''?> /></td>
			<td>
				<ul class="user-groups">
				<? foreach ($response->data['groups'] as $group) : ?>
					<li><input type="checkbox" name="user[<?=toHtml($user->getId())?>][groups][<?=toHtml($group->getId())?>]" value="1"<?=$user->getGroupCollection()->findOneById($group->getId()) ? ' checked="checked"' : ''?> /><?=toHtml($group->getName())?></li>
				<? endforeach; ?>
				</ul>
			</td>
		</tr>
	<? endforeach; ?>
	</table>
	<p class="simpleform-buttons">
		<input type="submit" value="<?=$sg->l('apply')?>" />
	</p>
</form>
