/* global kadenceProWooConfig */
/**
 * File Shop-init.js.
 * Gets Shop toggle working.
 */

(function() {
	'use strict';
	window.kadenceProWoo = {
		/**
		 * Find the cart and open it.
		 */
		triggerCart: function( sourceEvent ) {
			// Trigger the cart refresh to ensure the cart contents are updated.
			if ( document.body.querySelector( '[data-section="kadence_customizer_cart"]' ) ) {
				jQuery( document.body ).trigger( 'wc_fragment_refresh' );
			}

			// Backward compatibility code: If the cart is not open, open it.
			var drawerCartToggle = document.querySelector( '*[data-toggle-target="#cart-drawer"]' );
			if ( drawerCartToggle ) {
				// Initiate toggle.
				if ( typeof window?.kadence?.toggleDrawer === 'function' ) {
					window.kadence.toggleDrawer( drawerCartToggle );
				} else {
					var initLoadDelay = setInterval( function () {
						if ( typeof window?.kadence?.toggleDrawer === 'function' ) {
							window.kadence.toggleDrawer( drawerCartToggle );
							clearInterval( initLoadDelay );
						} else {
							console.log( 'No navigation toggle found' );
						}
					}, 200 );
				}
			}

			// WooCommerce 10.6+ compatibility code: If the cart is not open, open it.
			if ( 'added_to_cart' === sourceEvent ) {
				var blocksMiniCartButton = document.querySelector( 'button.wc-block-mini-cart__button' );
				if ( blocksMiniCartButton ) {
					var initLoadDelay = setInterval( function () {
						if ( document.body.querySelector( '[data-block-name="woocommerce/mini-cart"]' ) ) {
							document.dispatchEvent( new CustomEvent( 'wc-blocks_added_to_cart' ) );
						}
						clearInterval( initLoadDelay );
					}, 200 );
				}
			}
		},
		/**
		 * Initiate the script to toggle cart when product is added.
		 */
		initCartToggle: function() {
			// Legacy jQuery event — classic mini cart widget/shortcode.
			jQuery( document.body ).on( 'added_to_cart', function () {
				window.kadenceProWoo.triggerCart( 'added_to_cart' );
			} );

			// WooCommerce 10.6+ event — blocks mini cart.
			document.addEventListener( 'wc-blocks_added_to_cart', function () {
				window.kadenceProWoo.triggerCart( 'wc-blocks_added_to_cart' );
			} );

			if ( kadenceProWooConfig.openCart ) {
				window.kadenceProWoo.triggerCart();
			}
		},
		// Initiate the menus when the DOM loads.
		init: function() {
			window.kadenceProWoo.initCartToggle();
		}
	}
	if ( 'loading' === document.readyState ) {
		// The DOM has not yet been loaded.
		document.addEventListener( 'DOMContentLoaded', window.kadenceProWoo.init );
	} else {
		// The DOM has already been loaded.
		window.kadenceProWoo.init();
	}
})();
