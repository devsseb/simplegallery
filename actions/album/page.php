<?
	function htmlAlbums($albums, $level = 0) {
		$html = '<ul class="albums">';
		foreach ($albums as $album) {
			$html.= '<li>';
				$html.= '<a
					style="padding-left:' . (5 + $level * 15) . 'px;' . (get($_GET, k('id')) == $album->id ? 'background-color:rgba(119, 167, 197, 0.5);' : '') . '"
					href="?album&id=' . $album->id . '"
				>' . $album->name . '</a>';
				$html.= htmlAlbums($album->children, $level + 1);
			$html.= '</li>';
		}
		$html.= '</ul>';
		return $html;
	}
?>

<? if ($sg->albums->tree) : ?>
<div class="albums-menu">
	<?=htmlAlbums($sg->albums->tree)?>
</div>
<? else : ?>
<div id="no-album">
	No album found
</div>
<? endif; ?>
<? if ($id) : ?>
<div id="no-media">
	No media in this album
</div>
	<? if ($album->medias) : ?>
<div class="album" id="album-container">
	<div id="album" style="display:none;"><?=toHtml($album->id)?></div>
		<? if ($sg->user->admin) : ?>
	<form class="album-admin" method="post" action="?album&id=<?=toUrl($album->id)?>">
		<input type="hidden" name="id" value="<?=toHtml($album->id)?>" />
		<div class="album-admin-title">Album name :</div>
		<input class="album-admin-name" type="text" name="name" value="<?=toHtml($album->name)?>" />
		<div class="album-admin-title">Group access :</div>
		<table>
			<tr><th>H</th><th>F</th><th>G</th><th></th></tr>
<? foreach ($album->groups as $group => $access) :
	$accessInherited = $album->parent ? ($album->parent->groups[$group] < 0 ? $album->parent->groups[$group] : $album->parent->groups[$group]-2) : -2;
?>
			<tr>
				<td class="album-admin-radio album-admin-radio-inherited-<?=$accessInherited == -2 ? 'f' : 'g'?>">
					<input type="radio" name="access[<?=toHtml($group)?>]" value="<?=toHtml($accessInherited)?>" <?=$access < 0 ? 'checked="checked" ' : ''?>/>
				</td><td class="album-admin-radio album-admin-radio-f">
					<input type="radio" name="access[<?=toHtml($group)?>]" value="0" <?=$access == 0 ? 'checked="checked" ' : ''?>/>
				</td><td class="album-admin-radio album-admin-radio-g">
					<input type="radio" name="access[<?=toHtml($group)?>]" value="1" <?=$access == 1 ? 'checked="checked" ' : ''?>/>
				</td><td>
					<?=toHtml($group)?>
				</td>
			</tr>
<? endforeach; ?>
		</table>
		<div class="album-admin-button"><input type="submit" value="Apply" /></div>
	</form>
			<? endif; ?>
	<div class="album-action">
		<a class="album-action-download" href="?album&download=<?=toHtml($album->id)?>" target="_blank" title="Download album"></a>
	</div>
	<div class="media" id="media">
		<img class="media-loading" src="actions/album/loading.gif" alt="Loading" />
		<img id="mediaImage" src="actions/album/blank.gif" />
		<video id="mediaVideo" controls src="" width="500"></video>
		<div id="mediaUpdate">
			<ul id="mediaUpdateAction">
				<li title="Download"><a id="mediaDownload" target="_blank" href="#"></a></li>
				<li id="mediaRotateLeft" title="Rotate left"></li>
				<li id="mediaRotateRight" title="Rotate right"></li>
				<li id="mediaDelete" title="Delete"></li>
			</ul>
		</div>
	</div>
	<div class="thumbs" id="thumbs">
	<? foreach ($album->medias as $media) : ?>
		<a
			title="<?=toHtml($media->name)?>"
			class="thumb" 
			style="background-image:url('?media&amp;album=<?=toUrl($album->id)?>&amp;media=<?=toUrl($media->name)?>&amp;dim=75-short');"
			href="?media&amp;album=<?=toUrl($album->id)?>&amp;media=<?=toUrl($media->name)?>&amp;dim=500-long"
			mediaType="<?=toHtml($sg->getMediaType($media->name))?>"
			mediaWidth="<?=toHtml(get($media, k('width')))?>"
			mediaHeight="<?=toHtml(get($media, k('height')))?>"
<?/*			mediaOrientation="<?=toHtml(get($media, k('orientation')))?>"*/?>
		></a>
	<? endforeach; ?>
		<div id="thumb-current"></div>
	</div>
</div>
	<? endif; ?>
<? endif; ?>
