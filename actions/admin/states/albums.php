<div id="admin-locales"><?=json_encode(array(
	'album-check-cancelled' => l('admin.album-check-cancelled'),
	'album-check-end' => l('admin.album-check-end')
))?></div>
<ul class="admin-states">
	<li><a href="?admin=general"><?=l('admin.state.general')?></a></li>
	<li><?=l('admin.state.albums')?></li>
	<li><a href="?admin=users"><?=l('admin.state.users')?></a></li>
</ul>
<form class="box admin" action="?admin=albums" method="post">
	<h2><?=l('admin._')?></h2>
	<a href="?admin=albums&amp;albumsReload"><?=l('admin.albums-reload')?></a>
	<table class="admin-albums">
		<tr id="admin-albums-th" albumDepth="-1">
			<th><?=l('admin.albums-id')?></th>
			<th><?=l('admin.albums-tree')?></th>
			<th><?=l('admin.albums-name')?></th>
			<th><?=l('admin.albums-dates')?></th>
			<th><a href="#" id="adminAlbumsCheckAll"><?=l('admin.albums-check-all')?></a></th>
		</tr>
	<? foreach ($sg->albums as $album) :
		$start = $album->date_start;
		$end = $album->date_end;
		if (!$start)
			$start = $end;
		if (!$end)
			$end = $start;
		if (!$start)
			$start = $end = '0000-00-00';
		$date = $start . $end;
	?>
		<tr class="admin-album"
			albumName="<?=toHtml($album->name ? $album->name : basename($album->path))?>"
			albumDate="<?=$date?>"
			albumDepth="<?=$album->ns_depth?>"
		>
			<td class="admin-album-id">
				<?=toHtml($album->id)?>
				<input class="admin-album-position" type="hidden" name="albums[<?=toHtml($album->id)?>][position]" value="<?=toHtml($album->position)?>" />
			</td>
			<td class="admin-album-tree">
				<span class="album-spacer" style="padding-left:<?=$album->ns_depth * 15?>px;"></span>
				<img class="album-sort-manual" src="actions/admin/images/sort.png" title="<?=l('admin.album-sort-manual')?>" title="<?=l('admin.album-sort-manual')?>" />
				<?=toHtml(basename($album->path))?> <span class="admin-album-medias-total">(<?=$album->medias_total?>)</span>
			</td>
			<td>
				<span class="album-spacer" style="padding-left:<?=$album->ns_depth * 15?>px;"></span>
				<div class="album-sort">
		<? if ($album->ns_right - $album->ns_left > 1) : ?>
					<img class="album-sort-name" src="actions/admin/images/sort-alphabet.png" title="<?=l('admin.album-sort-name')?>" title="<?=l('admin.album-sort-name')?>" />
		<? endif; ?>
				</div>
				
				<input name="albums[<?=toHtml($album->id)?>][name]" placeholder="<?=toHtml(basename($album->path))?>" value="<?=toHtml($album->name)?>" class="admin-album-name" />
			</td>
			<td class="admin-album-dates">
				<span class="album-spacer" style="padding-left:<?=$album->ns_depth * 15?>px;"></span>
				<div class="album-sort">
		<? if ($album->ns_right - $album->ns_left > 1) : ?>
					<img class="album-sort-date" src="actions/admin/images/sort-date.png" title="<?=l('admin.album-sort-date')?>" title="<?=l('admin.album-sort-date')?>" />
		<? endif; ?>
				</div>
				<input type="date" placeholder="<?=l('date-format')?>" name="albums[<?=toHtml($album->id)?>][date_start]" value="<?=toHtml($album->date_start)?>" />
				<?=l('album.date.at')?>
				<input type="date" placeholder="<?=l('date-format')?>" name="albums[<?=toHtml($album->id)?>][date_end]" value="<?=toHtml($album->date_end)?>" />
			</td>
			<td class="admin-album-links">
				<a class="admin-album-check" href="?admin=check&amp;id=<?=toHtml($album->id)?>"><?=l('admin.album-check')?></a>
				<a class="admin-album-cancel" href="#"><?=strtolower(l('cancel'))?></a>
			</td>
		</tr>
	<? endforeach; ?>
	</table>
	<div class="box-submit"><input type="submit" value="<?=l('apply')?>" /></div>
	<div class="box-links">
		<a href="?"><?=l('cancel')?></a>
	</div>
</form>
