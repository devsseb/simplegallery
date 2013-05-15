window.addEvent('domready', function() {
	new Simplegallery();
});

var Simplegallery = new Class({
	initialize: function()
	{
		if (!$('media')) {
			if ($('no-media'))
				$('no-media').setStyle('display', 'block');
			return;
		}
		
		this.blank = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
		
		this.locale = JSON.decode($('album-locales').get('text'));

		this.mediaImage = $('mediaImage').addEvents({
			load: function(e) {
				var position = e.target.getPosition($('media'));
				var size = e.target.getSize();
				this.mediaUpdate.setStyles({
					left: position.x + size.x - 29,
					top: position.y + 5
				});
			}.bind(this),
			mouseover: function() {
				if (this.mediaImageLeave)
					clearTimeout(this.mediaImageLeave);
				this.mediaUpdate.setStyle('display', 'block');
			}.bind(this),
			mouseleave: function() {
				this.mediaImageLeave = this.mediaUpdate.setStyle.delay(500, this.mediaUpdate, ['display', 'none']);
			}.bind(this)
		});
		this.mediaVideo = $('mediaVideo');
		this.mediaUpdate = $('mediaUpdate').addEvents({
			mouseover: function() {
				if (this.mediaImageLeave)
					clearTimeout(this.mediaImageLeave);
				$('mediaDownload').set('href', '?media&album=' + this.album + '&media=' + this.media.get('title') + '&update=download');
				this.mediaUpdateAction.setStyle('display', 'block');
			}.bind(this),
			mouseleave: function() {
				this.mediaUpdateAction.setStyle('display', 'none');
			}.bind(this)
		});
		$('mediaRotateLeft').addEvent('click', function() {
			this.loadMedia(this.media, 'rotateLeft');
		}.bind(this));
		$('mediaRotateRight').addEvent('click', function() {
			this.loadMedia(this.media, 'rotateRight');
		}.bind(this));
		$('mediaDelete').addEvent('click', function() {
			if (confirm(this.locale['delete-confirm']))
				this.loadMedia(this.media, 'delete');
		}.bind(this));
		this.mediaUpdateAction = $('mediaUpdateAction');
		this.album = $('album').get('text');
		this.media = this.mediaImage.get('title');
		this.mediaOver = $('thumb-current');
		
		window.addEvent('keyup', function(e) {
			switch (e.key) {
				case 'left' :
					this.loadMedia(this.media.getPrevious());
				break;
				case 'right' :
					this.loadMedia(this.media.getNext());
				break;
			}
		}.bind(this));
		
		this.loadMedia($$('.thumb').addEvent('click', function(e) {
			this.loadMedia(e.target);
			return false;
		}.bind(this))[0]);
		
		
	},
	loadMedia: function(media, update)
	{
		if (!media)
			return;
		
		this.media = media;

		$$(this.mediaImage, this.mediaVideo).set({
			src: this.blank,
			styles: {display: 'none'}
		});
		this.mediaUpdate.setStyle('display', 'none');

		switch (this.media.get('mediaType')) {
			case 'image' :
				this.mediaImage.set({
					src: '?media&album=' + this.album + '&media=' + this.media.get('title') + '&dim=500-long' + (update ? '&update=' + update : ''),
					styles: {display: 'block'}
				});
			break;
			case 'video' :
				this.mediaVideo.removeProperties(/*'width', */'height');
				if (this.media.get('mediaWidth') < this.media.get('mediaHeight'))
					this.mediaVideo.set('height', 500);
/*				else
					this.mediaVideo.set('width', 500);*/
				this.mediaVideo.set({
					src: '?media&album=' + this.album + '&media=' + this.media.get('title') + (update ? '&update=' + update : ''),
					styles: {display: 'block'}
				});
			break;
		}
		if (update) {
		
			if (update == 'delete') {
				var media = this.media.getPrevious();
				if (!media)
					this.media.getNext();
				this.media.destroy();
				if (media)
					this.loadMedia(media);
				else {
					$('album-container').setStyle('display', 'none');
					$('no-media').setStyle('display', 'block');
				}
			} else {
				var url = this.media.getStyle('background-image');
				this.media.setStyle('background-image', 'url(' + this.blank + ')').setStyle('background-image', url);
			}
		}

		
		var position = this.media.getPosition($('thumbs'));
		this.mediaOver.setStyles({
			left: position.x - 2,
			top: position.y - 2,
			display: 'block'
		});

	}

});
