/**
 * Select terminal cell - v0.1.0
 *
 * Copyright 2016, Hibou (http://private.hibou-web.com)
 * Released under the GNU General Public License v2 or later
 */


(function($) {

    var wait_arr = [];

    $( '.each_repository td' ).on( 'click', function( e ) {

        var cell_value = $( this ).find( '.cell_name' ).text().toString();
        console.log( cell_value );
        var wait_cells = $( '.wait_cells' );
        //$( wait_cells ).prop( 'disabled', true );

        $( this ).toggleClass( 'active' );

        if ( $( this ).hasClass( 'active' ) ) {

            // Add array.
            wait_arr.push(cell_value);
            // Sort array.
            wait_arr.sort( function( a, b ) {

                a = a.toString();
                b = b.toString();
                if ( a < b ) {
                    return -1;
                } else if ( a > b ) {
                    return 1;
                }
                return 0;

            } );

            // Add value.
            $( wait_cells ).val( wait_arr );

        } else {

            var data_index = wait_arr.indexOf( cell_value );
            // Remove array
            wait_arr.splice( data_index, 1 );
            // Remove value
            $( wait_cells ).val( wait_arr );

        }

    } );

})(jQuery);