(function ( $ ) {
	"use strict";

	$(function () {

		// Place your administration-specific JavaScript here
		if($('.mark-posts-post-color').length > 0) {
			$('.mark-posts-post-color').each(function(index) {
				// color the whole row
				// $(this).parent().parent().find('th.check-column').css('background-color', $(this).data('color') );
			});
		}

	});

}(jQuery));