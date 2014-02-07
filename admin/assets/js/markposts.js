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


	// live preview of new markers
	$('.js-add-markers').keyup(function(e) { // use keyup instead of keypress for latest char
		var markers = [];
		var make_markers = $(this).val().split(","); // separate marker by comma
		$(make_markers).each(function(e) { // push each new marker to array
			markers.push('<span class="new-marker">'+make_markers[e]+'</span>');
		});

		$('.js-new-markers-intro').show();
		$('.js-new-markers').html(markers.join(' ')); // preview new markers

	});


}(jQuery));