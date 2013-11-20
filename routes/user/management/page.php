<form class="simpleform" method="post" action="?user=management">
	<div class="simpleform-caption">Groups</div>
	<p>
		<label for="group">Add a group : </label><br />
		<input type="text" id="group" name="group" value="<?=get($_POST, k('group'))?>" />
	</p>
	<p class="simpleform-buttons">
		<input type="submit" value="Add" />
	</p>
</form>
<ul class="groups">
<? foreach ($response->data['groups'] as $group) : ?>
	<li><?=toHtml($group->getName())?><sup><a title="delete" href="?user=management&groupdelete=<?=$group->getId()?>" onclick="return confirm('The deletion is permanent. You want to continue ?')">X</a></sup></li>
<? endforeach; ?>
</ul>
<form class="simpleform" method="post" action="?user=management">
	<div class="simpleform-caption">Add a user</div>
	<p>
		<label for="name">Name : </label><br />
		<input type="text" id="name" name="name" value="<?=get($_POST, k('name'))?>" />
	</p>
	<p>
		<label for="email">Email : </label><br />
		<input type="text" id="email" name="email" value="<?=get($_POST, k('email'))?>" />
	</p>
	<p class="simpleform-buttons">
		<input type="submit" value="Add" />
	</p>
</form>
<form class="simpleform user-form" method="post" action="?user=management">
	<div class="simpleform-caption">Users</div>
	<table class="users">
		<tr><th>Name</th><th>Email</th><th>Is admin</th><th>Groups</th></tr>
	<? foreach ($response->data['users'] as $user) : ?>
		<tr>
			<td>
				<?=toHtml($user->getName())?><?=$user->isActive() ? '' : ' (no active)'?>
				<sup><a title="delete" href="?user=management&userdelete=<?=$user->getId()?>" onclick="return confirm('The deletion is permanent. You want to continue ?')">X</a></sup>
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
		<input type="submit" value="Apply" />
	</p>
</form>
