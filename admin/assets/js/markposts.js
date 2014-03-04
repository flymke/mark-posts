(function ( $ ) {
	"use strict";

	// convert rgb to hex
	function rgb2hex(rgb) {
	  rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
	  function hex(x) {
	      return ("0" + parseInt(x).toString(16)).slice(-2);
	  }
	  return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
	}

	// modify hex for background usage
	function convertHex(hex,opacity){
	  hex = hex.replace('#','');
	  var r = parseInt(hex.substring(0,2), 16);
	  var g = parseInt(hex.substring(2,4), 16);
	  var b = parseInt(hex.substring(4,6), 16);
	  return 'rgba('+r+','+g+','+b+','+opacity/100+')';
	}

	// highlight each row
	$('.mark-posts-marker').each(function() {
  	var hex = rgb2hex($(this).css('backgroundColor'));
  	$(this).parent().parent().find('th, td').css('background-color', convertHex(hex,25));
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
	
	// change background color in edit post options
	if($('#mark_posts_options').length > 0) {
		var color = $('select#mark_posts_term_id option:selected').data('color');
		$('#mark_posts_options h3.hndle').css('background-color', convertHex(color,25));
		
		$('select#mark_posts_term_id').on('change', function() {
			var color = $('select#mark_posts_term_id option:selected').data('color');
			$('#mark_posts_options h3.hndle').css('background-color', convertHex(color,25));
			$('span.mark-posts-color').css('background-color', color);
		});
	}


}(jQuery));