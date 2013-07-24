<div id="admin-locales"><?=json_encode(array('user-delete-confirm' => l('admin.user-delete-confirm')))?></div>
<ul class="admin-states">
	<li><?=l('admin.state.general')?></li>
	<li><a href="?admin=albums"><?=l('admin.state.albums')?></a></li>
	<li><a href="?admin=users"><?=l('admin.state.users')?></a></li>
</ul>
<form class="box admin" action="?admin=general" method="post">
	<h2><?=l('admin._')?></h2>
	<div class="admin-parameters">
		<p>
			<label for="parameters-name"><?=l('admin.parameters.name')?> : </label>
			<input type="text" id="parameters-name" name="name" value="<?=toHtml($sg->parameters->name)?>" />
		</p>
		<p>
		<label for="parameters-locale"><?=l('admin.parameters.locale')?> : </label><select id="parameters-locale" name="locale">
			<option value=""<?=!$sg->parameters->locale ? ' selected="selected"' : ''?>><?=l('admin.parameters.locale-auto')?></option>
<? foreach ($sg->locale->langs as $lang) : ?>
			<option value="<?=toHtml($lang)?>"<?=$sg->parameters->locale == $lang ? ' selected="selected"' : ''?>><?=toHtml($lang)?></option>
<? endforeach; ?>
		</select>
		</p>
		<p>
			<label for="parameters-registration-disable"><?=l('admin.parameters.registration-disable')?> : </label>
			<input type="checkbox" id="parameters-registration-disable" name="registration-disable" value="1"<?=$sg->parameters->registration_disable ? ' checked="checked"' : ''?> />
		</p>
		<p>
			<label for="parameters-albums-calendar-disable"><?=l('admin.parameters.albums-calendar-disable')?> : </label>
			<input type="checkbox" id="parameters-albums-calendar-disable" name="albums-calendar-disable" value="1"<?=$sg->parameters->albums_calendar_disable ? ' checked="checked"' : ''?> />
		</p>
		<p>
			<label for="parameters-albums-comments-disable"><?=l('admin.parameters.albums-comments-disable')?> : </label>
			<input type="checkbox" id="parameters-albums-comments-disable" name="albums-comments-disable" value="1"<?=$sg->parameters->albums_comments_disable ? ' checked="checked"' : ''?> />
		</p>
		<p>
			<label for="parameters-albums-medias-dates-disable"><?=l('admin.parameters.albums-medias-dates-disable')?> : </label>
			<input type="checkbox" id="parameters-albums-medias-dates-disable" name="albums-medias-dates-disable" value="1"<?=$sg->parameters->albums_medias_dates_disable ? ' checked="checked"' : ''?> />
		</p>
		<p>
			<label for="parameters-albums-tags-disable"><?=l('admin.parameters.albums-tags-disable')?> : </label>
			<input type="checkbox" id="parameters-albums-tags-disable" name="albums-tags-disable" value="1"<?=$sg->parameters->albums_tags_disable ? ' checked="checked"' : ''?> />
		</p>
	</div>
	<div class="box-submit"><input type="submit" value="<?=l('apply')?>" /></div>
	<div class="box-links">
		<a href="?"><?=l('cancel')?></a>
	</div>
</form>
