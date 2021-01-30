( function( $ ) { 'use strict';
	$( document ).ready( function() {
		var SLUWadmin = {
			init: function() {
				this.selectCustomProductType( this );	
			},

			/**
			 * Select custom product type ( Product Edit Page )
			 */
			displayCourseProductTabs: function( productType ) {
				$.each( $( '#woocommerce-product-data select#product-type option' ), function( index, option ) {
					if( $( option ).val() == productType && productType == 'sluw_course' ) {
						$( option ).attr( 'selected', 'true' );
						$( '.wc-tabs li' ).removeClass( 'active' );
						$( '.woocommerce_options_panel' ).hide();
						$( '#sluw_course_tab' ).show();
						$( '.course-tab_options' ).addClass( 'active' );
						$( '.product_data_tabs.wc-tabs .general_options.general_tab' ).show();
						$( '.panel.woocommerce_options_panel .pricing' ).show();
						$( '.panel.woocommerce_options_panel .pricing' ).removeClass( 'hidden' );
					}
				} );
			},

			/**
			 * Select custom product type ( Product Edit Page )
			 */
			selectCustomProductType: function( self ) {
				if( $( '#woocommerce-product-data select#product-type' ).length > 0 ) {
					if( SLUW_ADMIN.productType != '' ) {
						self.displayCourseProductTabs( SLUW_ADMIN.productType );	
					}

					$( '#woocommerce-product-data select#product-type' ).on( 'change', function() {
						self.displayCourseProductTabs( $( this ).val() );
					} );
				}
			} 
		};
		SLUWadmin.init();
	} );
} )( jQuery );