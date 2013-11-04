window.addEvent('domready', function() {
	new SimpleGalleryLoader();
});

var SimpleGalleryLoader = new Class({
	initialize: function()
	{
		this.initAlbums();
		
		$('analyzer').addEvent('click', function(e) {
			e.preventDefault();
			
			var form = new Element('form', {method: 'post', action: '?album=analyzer', styles: {display: 'none'}}).inject(document.body);
			$$('.album-analyze:checked').get('value').each(function(id) {
				new Element('input', {name: 'albums[]', value: id}).inject(form);
			});
			
			form.submit();
		});
	},
	initAlbums: function()
	{
		$$('.album').each(function(domAlbum) {
		
			var album = {
				dom: {
					container: domAlbum,
					id: domAlbum.getElement('input[name=id]'),
					name: domAlbum.getElement('input[name=name]')
				}
			};
			album.id = album.dom.id.get('value');
			album.name = album.dom.name.get('value');
			
			domAlbum.store('album', album);
			
			album.dom.container.addEvent('click', function(e) {
				if (e.target.get('tag') == 'input')
					return;
				var cb = this.getElement('input[type=checkbox]');
				cb.set('checked', !cb.get('checked'));
			
			});
			
			album.dom.name.addEvents({
				keydown: function(e) {
					if (e.key == 'down') {
						var nextAlbum = this.getParent('.album').getNext('.album');
						if (nextAlbum)
							nextAlbum.getElement('.album-name').focus();
					} else if (e.key == 'up') {
						var previousAlbum = this.getParent('.album').getPrevious('.album');
						if (previousAlbum)
							previousAlbum.getElement('.album-name').focus();
					}
				},
				blur: function(e) {
					
					var album = e.target.getParent('.album').retrieve('album');
					
					if (album.name == e.target.get('value'))
						return;
						
					album.name = e.target.get('value');
				
					this.update(album);
				}.bind(this)
			});
		
		}.bind(this));
	},
	update: function(album)
	{
		new Request({
			url: '?album=update',
			data: {
				id: album.id,
				name: album.name
			}
		}).send();	
	}
});



