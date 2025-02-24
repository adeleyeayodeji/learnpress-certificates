/**
 * Load list certificates
 *
 * @since 4.0.9
 * @version 1.0.2
 */

import { listenElementCreated, lpFetchAPI } from '../utils';

const loadListCertificates = () => {
	let isLoadingMoreCer = 0;

	// Events
	document.addEventListener( 'click', ( e ) => {
		const target = e.target;
		if ( target.classList.contains( 'lp-cer-btn-load-more' ) ) {
			e.preventDefault();

			if ( ! isLoadingMoreCer ) {
				isLoadingMoreCer = 1;
				loadMoreCertificates( target );
			}
		}

		if ( target.classList.contains( 'button-assign-certificate' ) ) {
			e.preventDefault();
			const courseID = document.getElementById( 'post_ID' ).value;
			const themeCertificate = target.closest( '.theme' );
			const elCertificates = target.closest( '.lp-certificates' );
			const certificateID = themeCertificate.dataset.id;

			elCertificates.querySelectorAll( '.theme' ).forEach( ( el ) => {
				el.classList.remove( 'active' );
			} );

			themeCertificate.classList.add( 'active', 'updating' );

			// Update
			updateCerOfCourse( courseID, certificateID );
		}

		if ( target.classList.contains( 'button-remove-certificate' ) ) {
			e.preventDefault();
			e.stopPropagation();
			const courseID = document.getElementById( 'post_ID' ).value;
			const themeCertificate = target.closest( '.theme' );
			themeCertificate.classList.add( 'updating' );
			themeCertificate.classList.remove( 'active' );

			// Update
			updateCerOfCourse( courseID, 0 );
		}
	} );

	const updateCerOfCourse = ( courseId, certId ) => {
		const formData = new FormData();
		formData.append( 'lp-ajax', `update-course-certificate` );
		formData.append( 'course_id', courseId );
		formData.append( 'cert_id', certId );

		fetch( '', {
			method: 'POST',
			body: formData,
		} )
			.then( ( res ) => res.text() )
			.then( ( res ) => {
				const elCertificateBrowser = document.querySelector( '#certificate-browser' );
				const elThemes = elCertificateBrowser.querySelectorAll( '.theme.updating' );
				elThemes.forEach( ( el ) => {
					el.classList.remove( 'updating' );
				} );
			} )
			.catch( ( error ) => {
				console.log( error );
			} );
	};

	const loadMoreCertificates = ( btnLoadMore ) => {
		const textBtnLoadMore = btnLoadMore.textContent;
		const elTarget = btnLoadMore.closest( '.lp-target' );
		if ( ! elTarget ) {
			return;
		}

		btnLoadMore.textContent = localize_lp_cer_js.i18n.loading + '...';
		const dataSend = JSON.parse( elTarget.dataset.send );
		dataSend.args.paged = parseInt( dataSend.args.paged ) + 1;
		elTarget.dataset.send = JSON.stringify( dataSend );

		const url = lpDataAdmin.lp_rest_url + 'lp/v1/load_content_via_ajax/';
		const callBack = {
			success: ( response ) => {
				const elAddNewTheme = document.querySelector( '.add-new-theme' );
				const { status, message, data } = response;

				if ( 'success' === status ) {
					const elTmp = document.createElement( 'div' );
					elTmp.innerHTML = data.content;
					const elListCertificates = elTmp.querySelector( '.theme' );
					elAddNewTheme.insertAdjacentElement( 'beforebegin', elListCertificates );
					if ( data.paged === data.total_pages ) {
						btnLoadMore.remove();
					}
					buildCanvas();
				} else if ( 'error' === status ) {
					elTarget.innerHTML = message;
				}
			},
			error: ( error ) => {
				console.log( error );
			},
			completed: () => {
				isLoadingMoreCer = 0;
				btnLoadMore.textContent = textBtnLoadMore;
			},
		};

		window.lpAJAXG.fetchAPI( url, dataSend, callBack );
	};

	const buildCanvas = () => {
		const el_lp_data_config_cer = document.querySelectorAll( '.lp-data-config-cer:not(.loaded)' );
		el_lp_data_config_cer.forEach( ( el ) => {
			const data_config_cer = JSON.parse( el.value ) || {};
			const id_div_parent = '#' + el.closest( 'div' ).getAttribute( 'id' );

			window.LP_Certificate( id_div_parent, data_config_cer );
		} );
	};

	// Listen el courses load infinite have just created.
	listenElementCreated( ( node ) => {
		if ( node.classList.contains( 'lp-certificates' ) ) {
			buildCanvas();
		}
	} );
};

export default loadListCertificates;
