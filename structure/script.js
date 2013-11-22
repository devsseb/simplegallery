window.addEvent('domready', function() {

	var menuButton = $('menu-button');
	var menuUnroll = $('menu-unroll');
	if (menuButton) {
	
		menuButton.addEvents({
			mouseenter: function() {
				menuButton.isOpen = true;
				menuButton.addClass('open');
				menuUnroll.setStyle('display', 'block');
			},
			mouseleave: function() {
				menuButton.isOpen = false;
				(function() {
					if (!menuButton.isOpen) {
						menuButton.removeClass('open');
						menuUnroll.setStyle('display', 'none');
					}
				}).delay(250);

			}
		});
		menuUnroll.addEvents({
			mouseenter: function() {
				menuButton.isOpen = true;
				menuButton.addClass('open');
				menuUnroll.setStyle('display', 'block');
			},
			mouseleave: function() {
				menuButton.isOpen = false;
				(function() {
					if (!menuButton.isOpen) {
						menuButton.removeClass('open');
						menuUnroll.setStyle('display', 'none');
					}
				}).delay(250);

			}
		});
	
	}

	var headerHeight = $('header').getSize().y;
	
	$('content').addEvent('scroll', function(e) {
		if (this.getScroll().y > headerHeight && !document.body.hasClass('header-reduce'))
			document.body.addClass('header-reduce');
		else if (this.getScroll().y <= headerHeight && document.body.hasClass('header-reduce'))
			document.body.removeClass('header-reduce');
	});

	if ($('sgUser'))
		window.sgUser = JSON.decode($('sgUser').get('value'));

});

