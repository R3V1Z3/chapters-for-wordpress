ChaptersForWordPress = ( function( $ ) {	
	return {
		init: function() {

			// Get total chapters.
			var total_chapters = $( 'div[data-number="1"]' ).data('total');

			// Get chapter hash from url.
			var chapter = window.location.hash.substring(1);
			if ( !chapter ) {
				chapter = '1';
			} else {
				chapter = chapter.split('-')[1];
			}
			if ( chapter > total_chapters ) chapter = 1;

			// Hide other chapters and show selected one. TODO: DRY
			$( 'div[data-number]' ).hide();
			$( 'div[data-number="' + chapter + '"]' ).show();

			// Setup click event for chapter list anchor links.
			$( 'a[data-number]' ).click( function() {

				// Hide other chapters and show selected one.
				// TODO: Create simple function for this. DRY.
				$( 'div[data-number]' ).hide();
				$( 'div[data-number="' + $(this).data('number') + '"]' ).show();
			});
		},
	};

})( jQuery );

jQuery( document ).ready( function(){
	ChaptersForWordPress.init();
});