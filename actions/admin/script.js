window.addEvent('domready', function() {

	if ($('user-add-link')) {

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
	}
	
	if ($('adminGroupAdd'))
		$('adminGroupAdd').addEvent('click', function() {
			new Element('li').grab(new Element('input', {
				type: 'text',
				name: 'groups-new[]'
			})).inject($('adminGroups'));
			
			return false;
		});
	
	if ($('adminAlbumsCheckAll')) {
		$('adminAlbumsCheckAll').addEvent('click', function(e) {
			$$('.admin-album-check').setStyle('display', 'none');
			this.getParent('table').getElement('.admin-album-check').fireEvent('click');
		});
	
		$$('.admin-album-check').addEvent('click', function(e) {
			if (e)
				e.preventDefault();
			var next = e == undefined;
			
			var tr = this.getParent('tr').getNext('tr');
			if (tr && tr.getElement('td').get('colspan') == 5)
				tr.getElement('td').destroy();
			this.store('result', new Element('div').inject(new Element('td.admin-album-check-result[colspan=5]').inject(new Element('tr').inject(this.getParent('tr'), 'after'))));
			this.setStyle('display', 'none');

			$('adminAlbumsCheckAll').setStyle('display', 'none');
			
			this.store('request', new Request.HTML({
				url: this.get('href'),
				update: this.retrieve('result'),
				onProgress: function(e, xhr) {
					this.options.update.set('html', xhr.response);
					this.options.update.scrollTop = this.options.update.scrollHeight;
				},
				onSuccess: function() {
					var locale = JSON.decode($('admin-locales').get('text'));
					this.options.update.grab(new Element('strong.album-check-end', {html: '<br />' + locale['album-check-end']}));

					this.options.update.scrollTop = this.options.update.scrollHeight;

					$$('.admin-album-cancel').setStyle('display', 'none');
					if (next) {
						var tr = this.options.update.getParent('tr').getNext('tr');
						if (tr && tr.getElement('td').get('colspan') == 5)
							tr = tr.getNext('tr');
						if (tr) {
							tr.getElement('.admin-album-check').fireEvent('click');
						}
					} else {
						$$('.admin-album-check').setStyle('display', 'inline');
						$('adminAlbumsCheckAll').setStyle('display', 'inline');
					}
					
				}
			}).send());
			this.getNext().setStyle('display', 'inline');
		});

		$$('.admin-album-cancel').addEvent('click', function(e) {
			e.preventDefault();
			var check = this.getPrevious();
		
			this.setStyle('display', 'none');
		
			check.retrieve('request').cancel();
			var locale = JSON.decode($('admin-locales').get('text'));
			check.retrieve('result').grab(new Element('strong.album-check-cancelled', {html: '<br />' + locale['album-check-cancelled']}));
			check.retrieve('result').scrollTop = check.retrieve('result').scrollHeight;
		
			$$('.admin-album-cancel').setStyle('display', 'none');
			$$('.admin-album-check').setStyle('display', 'inline');
			$('adminAlbumsCheckAll').setStyle('display', 'inline');
		});
	}
});
