/* eslint-disable camelcase */
( function( $ ) {
	let $html, el_lp_data_config_cer, el_show_cer_popup_first,
		el_form_certificate_button, el_popup_cert, el_single_certificate,
		el_social_cert, el_need_upload_cert_img_to_server;

	let el_form_lp_cert_add_to_cart_woo;
	let el_form_lp_cert_add_to_cart_lp;

	window.LP_Certificate = function( el, options ) {
		let self = this,
			viewport = {
				width: 0,
				height: 0,
				templateWidth: 0,
				templateHeight: 0,
				ratio: 1,
			},
			$el = $( el ),
			$canvas = null,
			windowHeight, windowWith;

		let el_certificate_actions, el_download, el_certificate_result;

		function init() {
			windowHeight = $( window ).height();
			windowWith = $( window ).width();
			getElements();
			initCanvas();
			$( document ).on( 'click', '[data-cert="' + $el.attr( 'id' ) + '"]', function( e ) {
				e.preventDefault();
				download();
			} );
			self.$canvas = $canvas;
		}

		function getElements() {
			el_certificate_actions = $( '.certificate-actions' );
		}

		function initCanvas() {
			if ( ! $canvas ) {
				$canvas = $el.find( 'canvas' );
				$canvas = new fabric.Canvas( $canvas.get( 0 ) );
				$canvas.selection = false;

				$.each( options.layers, function( i ) {
					total_layer++;
				} );

				$.each( options.layers, function( i, layer ) {
					if ( ! layer.type ) {
						return;
					}

					if ( $.isPlainObject( layer ) ) {
						createLayer( layer );
					}
				} );
			}
		}

		function dataURItoBlob( dataURI ) {
			// convert base64 to raw binary data held in a string
			// doesn't handle URLEncoded DataURIs - see SO answer #6850276 for code that does this
			const byteString = atob( dataURI.split( ',' )[ 1 ] );

			// separate out the mime component
			const mimeString = dataURI.split( ',' )[ 0 ].split( ':' )[ 1 ].split( ';' )[ 0 ];

			// write the bytes of the string to an ArrayBuffer
			const ab = new ArrayBuffer( byteString.length );

			// create a view into the buffer
			const ia = new Uint8Array( ab );

			// set the bytes of the buffer to the correct values
			for ( let i = 0; i < byteString.length; i++ ) {
				ia[ i ] = byteString.charCodeAt( i );
			}

			// write the ArrayBuffer to a blob, and you're done
			const blob = new Blob( [ ab ], { type: mimeString } );
			return blob;
		}

		let name_file_download = 'certificate';

		function download() {
			if ( el_download.length ) {
				const downloadType = el_download.data( 'type-download' );

				if ( undefined !== options.name ) {
					name_file_download = options.name;
				}

				switch ( downloadType ) {
				case 'pdf':
					downloadPDF();
					break;
				case 'image':
				default:
					downloadImage();
					break;
				}
			}
		}

		function downloadImage() {
			const args = {
				format: 'png',
				multiplier: 1 / $canvas.getZoom(),
			};

			const data_url = $canvas.toDataURL();
			const imageBlob = dataURItoBlob( data_url );
			const ajaxData = new FormData();

			name_file_download = name_file_download + '.png';

			ajaxData.append( 'files', imageBlob, name_file_download );

			downloadjs( $canvas.toDataURL( args ), name_file_download );

			return false;
		}

		function createPreview() {
			const args = {
				format: 'png',
				multiplier: 1 / $canvas.getZoom(),
				quality: 1,
			};

			const data = $canvas.toDataURL( args );

			const $img = $( '<img class="certificate-result" />' ).insertBefore( '#' + $el[ 0 ].id );

			el_certificate_result = $( '.certificate-result' );

			$img.attr( 'src', data );

			// Resize image certificate preview
			setTimeout( function() {
				if ( $img.width() > windowWith ) {
					el_certificate_result.css( 'width', '100%' );
				}
			}, 100 );
		}

		let total_layer = 0;
		let total_layer_loaded = 0;

		function check_layers_added_done() {
			total_layer_loaded++;

			if ( total_layer_loaded === total_layer ) {
				const url_img_cer_bg = options.template;
				//var url_img_cer_bg = 'https://cdn.glitch.com/4c9ebeb9-8b9a-4adc-ad0a-238d9ae00bb5%2Fmdn_logo-only_color.svg?1535749917189';

				const img_cer_bg = new Image();
				const args_fabric = {};

				// Check image crossOrigin
				if ( undefined !== localize_lp_cer_js ) {
					const is_same_domain = new RegExp( '^' + localize_lp_cer_js.base_url );

					if ( ! is_same_domain.test( url_img_cer_bg ) ) {
						img_cer_bg.crossOrigin = 'Anonymous';
						args_fabric.crossOrigin = 'Anonymous';
					}
				}

				img_cer_bg.onload = function() {
					viewport = {
						width: this.width,
						height: this.height,
					};

					fabric.Image.fromURL( img_cer_bg.src, function( img ) {
						$canvas.backgroundImage = img;
						updateView();
						createPreview();

						if ( el_need_upload_cert_img_to_server.length ) {
							saveImageToServer();
						}

						if ( el_certificate_actions.length ) {
							el_download = el_certificate_actions.find( '.download' );
						}

						const elCerPreview = document.querySelector( el );
						if ( elCerPreview ) {
							const elCerConfig = elCerPreview.querySelector( '.lp-data-config-cer' );
							elCerConfig.classList.add( 'loaded' );
						}

						// Show popup certificate
						$( document ).triggerHandler( 'learn-press/certificates/loaded' );
					}, args_fabric );
				};

				img_cer_bg.src = url_img_cer_bg;
			}
		}

		function saveImageToServer() {
			const data = {
				action: 'lpCertCreateImage',
				data64: el_certificate_result.attr( 'src' ),
				name_image: options.key_cer,
			};

			$.ajax( {
				url: localize_lp_cer_js.url_ajax,
				data,
				method: 'post',
				dataType: 'json',
				beforeSend() {
					el_certificate_actions.append( '<li class="fa fa-spinner">Loading share social...</li>' );
				},
				success( rs ) {
					if ( rs.code === 1 ) {
						$.each( el_social_cert, function( e ) {
							const elLink = $( this ).find( 'a' );
							const hrefShare = elLink.attr( 'href' );
							const url_cert_share = hrefShare + rs.url_cert;
							elLink.attr( 'href', url_cert_share );
						} );

						el_social_cert.show();
					}
				},
				complete() {
					el_certificate_actions.find( '.fa-spinner' ).remove();
				},
				error( e ) {
					console.log( e );
				},
			} );
		}

		function htmlDecode( input ) {
			const e = document.createElement( 'div' );
			e.innerHTML = input;
			return e.childNodes.length === 0 ? '' : e.childNodes[ 0 ].nodeValue;
		}

		function createLayer( args ) {
			args.text = htmlDecode( args.text ) || '';

			const defaults = $.extend( {
					fontSize: 24,
					left: 0,
					top: 0,
					lineHeight: 1,
					originX: 'center',
					originY: 'center',
					fontFamily: 'Helvetica',
					fieldType: 'custom',
					qr_size: 40,
				}, args ),
				text = args.text || '';

			let $object = null;

			try {
				const is_url = /^(https?|s?ftp):\/\//i.test( args.text );

				if ( args.fieldType === 'verified-link' && is_url ) {
					const qr_code = new Image();
					qr_code.crossOrigin = 'Anonymous';

					qr_code.onload = function() {
						$object = new fabric.Image( null, defaults );
						$object.setSrc( qr_code.src, function() {}, { crossOrigin: 'Anonymous' } );
						$canvas.add( $object );
						//console.log(qr_code.src);
						check_layers_added_done();
					};

					qr_code.src = args.text;
				} else {
					const wrap = ( s ) => s.replace(
						/(?![^\n]{1,64}$)([^\n]{1,64})\s/g, '$1\n'
					);

					$object = new fabric.Text( '', defaults );

					$object.text = wrap( $object.text );
					//console.log($object.text);

					$canvas.add( $object );

					check_layers_added_done();
				}
			} catch ( e ) {
				console.log( e );
			}

			return $object;
		}

		function getMaxWidth() {
			return $el.width();
		}

		function updateView() {
			$canvas.setHeight( viewport.height );
			$canvas.setWidth( viewport.width );
			$canvas.setZoom( viewport.ratio );
			$canvas.calcOffset();
			$canvas.renderAll();

			//fitImage();
		}

		function fitImage() {
			let $preview = $el.siblings( '.certificate-result' ),
				scrWidth = $el.parent().width(),
				scrHeight = $( window ).height() - ( 60 + parseInt( $el.parent().position().top ) ),
				maxWidth = viewport.width,
				maxHeight = viewport.height;

			const scale = Math.min(
				scrWidth / maxWidth,
				scrHeight / maxHeight
			);

			maxWidth = maxWidth * scale;
			if ( maxWidth ) {
				$preview.css( {
					//maxWidth: maxWidth * scale
				} );
			}
		}

		function setLayerProp( $layer, prop, value ) {
			const options = {};
			switch ( prop ) {
			case 'textAlign':
				//$layer.originX = value;
				break;
			case 'color':
				//$layer.set('fill', value);
				options.fill = value;
				break;
			case 'scaleX':
			case 'scaleY':
				if ( value < 0 ) {
					if ( prop === 'scaleX' ) {
						$layer.flipX = true;
					} else {
						$layer.flipY = true;
					}
				} else if ( prop === 'scaleX' ) {
					$layer.flipX = false;
				} else {
					$layer.flipY = false;
				}
				options[ prop ] = ( Math.abs( value ) );
				break;
			case 'top':
			case 'left':
			case 'angle':
				options[ prop ] = parseInt( value );
				break;
			default:
				options[ prop ] = value;
			}
			$.each( options, function( k, v ) {
				$layer.set( k, v );
			} );
			$layer.setCoords();
		}

		function downloadPDF() {
			const getImageFromUrl = function( url, callback ) {
				const img = new Image();
				// img.crossOrigin = 'anonymous'

				img.onError = function() {
					alert( 'Cannot load image: "' + url + '"' );
				};
				img.onload = function() {
					callback( img, img.width, img.height );
				};
				img.src = url;
			};

			const createPDF = function( imgData, width, height ) {
				let doc, pdfWidth, pdfHeight;

				if ( width >= height ) {
					doc = new jsPDF( 'l', 'mm', [ width, height ], true );
					pdfWidth = doc.internal.pageSize.getWidth();
					pdfHeight = ( height * pdfWidth ) / width;
				} else {
					doc = new jsPDF( 'p', 'mm', [ width, height ], true );
					pdfWidth = doc.internal.pageSize.getWidth();
					// pdfHeight = (height * pdfWidth) / width;
					pdfHeight = doc.internal.pageSize.getHeight();
				}

				doc.addImage( imgData, 'jpg', 0, 0, pdfWidth, pdfHeight, '', 'FAST' );
				doc.save( name_file_download + '.pdf' );
			};

			el_certificate_result = $( '.certificate-result' );

			if ( el_certificate_result.length ) {
				const url = el_certificate_result.attr( 'src' );

				getImageFromUrl( url, createPDF );
			}
		}

		init();
	};

	function getElements() {
		$html = $( 'html, body' );
		el_lp_data_config_cer = $( '.lp-data-config-cer' );
		el_show_cer_popup_first = $( 'input[name=f_auto_show_cer_popup_first]' );
		el_form_certificate_button = $( 'form[name="certificate-form-button"]' );
		el_popup_cert = $( '#certificate-popup' );
		el_single_certificate = $( '.single-certificate-content' );
		el_social_cert = $( '.share-social-cert' );
		el_need_upload_cert_img_to_server = $( 'input[name=need_upload_cert_img_to_server]' );

		el_form_lp_cert_add_to_cart_woo = $( 'form[name=form-lp-cert-add-to-cart-woo]' );
		el_form_lp_cert_add_to_cart_lp = $( 'form[name=form-lp-cert-purchase]' );
	}

	function popupCer() {
		if ( el_popup_cert.length ) {
			function close() {
				el_popup_cert.fadeOut( function() {
					$html.css( 'overflow', 'auto' );
				} );
			}

			function open() {
				$html.css( 'overflow', 'hidden' );
				el_popup_cert.fadeIn();
			}

			$( document ).on( 'learn-press/certificates/loaded', function() {
				el_popup_cert.addClass( 'ready' ).hide();

				$html
					.on( 'keyup', function( e ) {
						if ( e.keyCode === 27 ) {
							close();
						}
					} )
					.on( 'click', '.close-popup', function( e ) {
						close();
						e.preventDefault();
					} );

				el_form_certificate_button.on( 'submit', function( e ) {
					e.preventDefault();
					open();
				} );

				if ( el_show_cer_popup_first.length ) {
					open();
				}
			} );
		}
	}

	function addCertToCartWoo( form ) {
		const lang = lpData.urlParams.lang ? `?lang=${ lpData.urlParams.lang }` : '';
		const btn = form.querySelector( '.btn-add-cert-to-cart-woo' );
		const formData = new FormData( form );
		const data = Object.fromEntries( formData.entries() );
		data.action = 'lp_cert_add_to_cart_woo';

		$.ajax( {
			url: localize_lp_cer_js.url_ajax + lang,
			data,
			method: 'post',
			beforeSend() {
				btn.classList.add( 'loading' );
			},
			success( rs ) {
				if ( rs.code === 1 ) {
					if ( undefined !== rs.redirect_to ) {
						window.location.replace( rs.redirect_to );
					} else {
						form.closest( '.wrapper-lp-cert-add-to-cart-woo' ).insertAdjacentHTML( 'beforeend', rs.button_view_cart );
						form.remove();
					}
				} else {
					alert( rs.message );
				}
			},
			error( e ) {
				console.log( e );
			},
			complete() {
				btn.classList.remove( 'loading' );
			},
		} );
	}

	/**
	 * Event add certificate to cart LP
	 * @param form
	 */
	function addCertToCartLP( form ) {
		let message = '';
		let status = '';
		const formData = new FormData( form );
		const data = Object.fromEntries( formData.entries() );
		const btn = form.querySelector( '.btn-purchase-certificate' );
		btn.classList.add( 'loading' );

		const newElMessage = document.createElement( 'div' );
		newElMessage.classList.add( 'learn-press-message' );

		wp.apiFetch( {
			path: '/lp/v1/certificate/purchase',
			method: 'POST',
			data,
		} ).then( ( res ) => {
			const { data } = res;
			status = res.status;
			message = res.message;

			newElMessage.classList.add( status );
			newElMessage.innerHTML = message;
			form.insertAdjacentElement( 'beforeend', newElMessage );

			if ( undefined !== status && status === 'success' ) {
				if ( data.redirect ) {
					setTimeout( function() {
						window.location.href = data.redirect;
					}, 800 );
				}
			}
		} ).catch( ( err ) => {
			newElMessage.classList.add( 'error' );
			newElMessage.innerHTML = err.message;
			form.insertAdjacentElement( 'beforeend', newElMessage );
		} ).then( () => {
			btn.remove();
		} );
	}

	$( document ).ready( function() {
		getElements();
		el_social_cert.hide();

		if ( ! el_show_cer_popup_first.length ) {
			el_form_certificate_button.css( 'display', 'inline-block' );
		}

		//console.log(localize_lp_cer_js);

		/*** Create certificates ***/
		if ( el_lp_data_config_cer.length ) {
			try {
				$.each( el_lp_data_config_cer, function( i ) {
					const data_config_cer = JSON.parse( $( this ).val() ) || {};

					$( this ).val( '' );
					const id_div_parent = '#' + $( this ).closest( 'div' ).attr( 'id' );

					const cer = LP_Certificate( id_div_parent, data_config_cer );

					//console.log(data_config_cer);
				} );
			} catch ( e ) {
				console.log( e );
			}
		}

		// Popup Certificate
		popupCer();

		// Events submit form
		document.addEventListener( 'submit', function( e ) {
			const el = e.target;

			if ( el.getAttribute( 'name' ) === 'form-lp-cert-purchase' ) {
				e.preventDefault();
				addCertToCartLP( el );
			} else if ( el.getAttribute( 'name' ) === 'form-lp-cert-add-to-cart-woo' ) {
				e.preventDefault();
				addCertToCartWoo( el );
			}
		} );
	} );
}( jQuery ) );

