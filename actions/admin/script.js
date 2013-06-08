window.addEvent('domready', function() {

	$('user-add-link').addEvent('click', function() {
		if ($('user-add').getStyle('display') == 'none')
			$('user-add').setStyle('display', 'block').getElement('input[name=user-name]').focus();
		else {
			$('user-add').setStyle('display', 'none').getElements('input[name^=user-]').set('value', '');
		}
		return false;
	});

	$$('.admin-user-delete a').addEvent('click', function() {
		var locale = JSON.decode($('admin-locales').get('text'));
		return confirm(locale['user-delete-confirm']);
	});

});
