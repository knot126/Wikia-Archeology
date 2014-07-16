require(['jquery', 'sloth'], function ($, sloth) {
	'use strict';

	/**
	 * @desc Obtains a thumbnail for the map, with right dimensions
	 * @param {HTMLElement} element - map figure that was lazy-loaded
	 */
	function getThumbnail(element) {
		var img = element.getElementsByTagName('img')[0];
		$.nirvana.sendRequest({
			controller: 'WikiaInteractiveMapsParserTag',
			method: 'getMobileThumbnail',
			data: {
				image: img.getAttribute('data-src'),
				width: img.offsetWidth,
				height: img.offsetHeight
			}
		}).done(function(data) {
			img.src = data.src;
		});
	}

	sloth({
		on: document.getElementsByClassName('wikia-interactive-map-thumbnail'),
		callback: getThumbnail
	});
});
