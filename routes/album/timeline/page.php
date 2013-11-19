<?
$lastDate = null;
foreach ($response->data['medias'] as $media) : ?>
	<? if (substr($media->datesort, 0, 10) != $lastDate) : ?>
		<? if ($lastDate) : ?>
		
	</div>
		<? endif; ?>
	<div class="timeline-date">
	<? endif; ?>
	
	<?=basename($media->path)?>
<?
	$lastDate = substr($media->datesort, 0, 10);
endforeach; ?>
