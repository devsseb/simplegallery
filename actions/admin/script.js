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
		
		$$('.admin-album').addEvents({
			mouseenter: function() {

				if (window.albumSort)
					return;
			
				albumTrs(this).addClass('hoverChildren');
				
			},
			mouseleave: function() {
				this.getParent().getElements('tr').removeClass('hoverChildren');
			}
		});
		
		$$('.album-sort-name,.album-sort-date').addEvent('click', function() {
			var trThis = this.getParent('tr');
			var me = trThis.get('albumDepth');
			var trsToSort = [];
			var trs = trThis.getAllNext('tr');
			for (var i = 0,tr; tr = trs[i]; i++) {
				var him = tr.get('albumDepth');
				if (me >= him)
					break;

				if (me + 15 == him) {
					trsToSort.push(tr);
					tr.albumChildren = [];
				} else {
					trsToSort.getLast().albumChildren.push(tr);
				}
			}
			
			var trsSort = [];
			
			var field = this.hasClass('album-sort-name') ? 'albumName' : 'albumDate';

			for (var i = 0,tr; tr = trsToSort[i]; i++) {
				var sort = false;
				for (var j = 0,trSort; trSort = trsSort[j]; j++) {
					if (sort = (
						(this.asc && tr.get(field) < trSort.get(field)) ||
						(!this.asc && tr.get(field) > trSort.get(field))
					)) {
						trsSort.splice(i, 0, tr);
						break;
					}
				}
				if (!sort)
					trsSort.unshift(tr);
			}

			this.asc = !this.asc;
			
			trsSort.reverse();
			
			for (var i = 0,trSort; trSort = trsSort[i]; i++) {
				for (var j = 0,child; child = trSort.albumChildren[j]; j++)
					child.inject(trThis, 'after');
				trSort.inject(trThis, 'after');
			}
			
			setAlbumsPosition();
		});
		
		$$('.album-sort-manual').addEvents({
			mousedown: function(e) {
				e.preventDefault();
				
				trs = albumTrs(this.getParent('tr'));
				trs.unshift(this.getParent('tr'));
				
				var parent = trs[0].getPrevious('[albumDepth=' + (trs[0].get('albumDepth') - 1) + ']');
				if (!parent)
					parent = $('admin-albums-th');
				
				var target = albumTrs(parent);
				target.unshift(parent);

				for (var i = 0,tr;tr=trs[i];i++)
					target.erase(tr);

				window.albumSort = {
					album: trs,
					parent: parent,
					target: target,
					depth: trs[0].get('albumDepth'),
					after: trs[0].getPrevious()
				};
				window.albumSort.album.addClass('album-sorting');
				
				document.body.setStyle('cursor', 'move');
			}
		});

		$$('#admin-albums-th,.admin-album').addEvent('mousemove', function() {
				if (!window.albumSort)
					return;
				
				if (!window.albumSort.target.contains(this))
					return;

				// Retrieve after album

				window.albumSort.after = this;
				var trsAfter = this.getAllNext();
				for (var i = 0,tr; tr = trsAfter[i];i++) {

					if (!window.albumSort.target.contains(tr))
						break;

					if (tr.get('albumDepth') == window.albumSort.depth)
						break;
						
					window.albumSort.after = tr;

				}

				new Elements(window.albumSort.album.clone().reverse()).inject(window.albumSort.after, 'after');

		});
		
		window.addEvents({
			mouseup: function() {
				if (!window.albumSort)
					return;

				window.albumSort.album.removeClass('album-sorting');
				
				delete window.albumSort;
				
				document.body.setStyle('cursor', 'default');
				
				setAlbumsPosition();

			}
		});
		
		setAlbumsPosition();
	}
});

function albumTrs(trThis)
{

	var me = trThis.get('albumDepth');

	var trs = trThis.getAllNext('tr');

	var album = new Elements();
	for (var i = 0,tr; tr = trs[i]; i++) {
		if (me >= tr.get('albumDepth'))
			break;
		album.push(tr);
	}
	return album;
}

function setAlbumsPosition()
{
	var positions = {};
	var trs = $$('.admin-album');
	var lastDepth = -1;
	for (var i = 0,tr;tr = trs[i];i++) {
		var depth = tr.get('albumDepth');
		if (depth < lastDepth)
			delete positions[lastDepth];
		
		if (!positions[depth])
			positions[depth] = 0;
		
		tr.getElement('.admin-album-position').set('value', ++positions[depth]);
		
		lastDepth = depth;

	}
}
