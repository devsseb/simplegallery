window.addEvent('domready', function() {
	
	window.addEvent('resize', structureResize);
	structureResize();
	
});

function structureResize()
{
	var windowSize = window.getSize();

	var menuSize = 50;

	if (windowSize.x > windowSize.y) {

		if ($('menu'))
			$('menu').setStyles({
				display: 'block',
				width: menuSize,
				height: windowSize.y
			});
		else
			menuSize = 0;
		
		$('content').setStyles({
			left: menuSize,
			top: 0,
			width: windowSize.x - menuSize,
			height: windowSize.y
		});

	
	} else {

		if ($('menu'))
			$('menu').setStyles({
				display: 'block',
				width: windowSize.x,
				height: menuSize
			});
		else
			menuSize = 0;
		
		$('content').setStyles({
			left: 0,
			top: menuSize,
			width: windowSize.x,
			height: windowSize.y - menuSize
		});
	}

}
