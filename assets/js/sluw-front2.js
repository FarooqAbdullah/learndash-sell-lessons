( function($) { 'use strict';
    $(document).ready(function() {

        var SLUWFront = {
            init: function() {
                this.changeWoocommerceAttribute();
                this.removeRequiredAttr();
                this.makeLessonRowRelative();
            },

            makeLessonRowRelative: function() {
                if( $( '.ld-item-list-item-preview' ).length > 0 ) {
                    $( '.ld-item-list-item-preview' ).css( 'position', 'relative' );
                }
            },

            removeRequiredAttr: function() {
                if( $( '.sluw-lessons-cb' ).length > 0 ) {
                    $( '.sluw-lessons-cb' ).on( 'change', function() {
                        var cbChecked = false;
                        $.each( $( '.sluw-lessons-cb' ), function( index, elem ) {
                            if( $( elem ).prop( 'checked' ) ) {
                                $( '.sluw-lessons-cb' ).removeAttr( 'required' );
                                cbChecked = true;
                                return false;
                            }
                        } );

                        if( ! cbChecked ) {
                            $( '.sluw-lessons-cb' ).attr( 'required', 'true' );
                        }
                    } );
                }
            },

            changeWoocommerceAttribute: function() {
                $( '.variation p' ).attr( "id", "sluw_custom_margin" );
            }
        }

        SLUWFront.init();
    });
})(jQuery);