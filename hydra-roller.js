jQuery( document ).ready( () => {
	const $ = jQuery;
	const actionUrl = hydra_data.action_url;
	const historyUrl = hydra_data.history_url;
	const userId = hydra_data.user_id;
	const $results = $( '#hydra-results' );
	const $btn = $( '#hydra-roll' );
	const $leader = $( '#hydra-leader' );
	const $flipBtn = $( '#hydra-flip' );
	const $fixBtn = $( '#hydra-fix' );
	let lastRender = 0;
	let acting = false;

	const formatDate = ( timestamp ) => {
		const date = new Date( timestamp * 1000 );
		return `${ date.getFullYear() }-${ date.getMonth() + 1 }-${ date.getDate() } ${ date.getHours() }:${ date.getMinutes() }`;
	};

	const stringToColor = ( str ) => {
		let hash = 0;
		for ( let i = 0; i < str.length; i++ ) {
			hash = str.charCodeAt(i) + ((hash << 5) - hash);
		}
		let color = '#';
		for ( let i = 0; i < 3; i++ ) {
			let value = ( hash >> ( i * 8 ) ) & 0xFF;
			color += ( '00' + value.toString( 16 ) ).substr( -2 );
		}
		return color;
	};

	const unescapeHtml = ( text ) => {
		return $( '<div>' ).html( text ).text();
	};

	const renderAction = ( event ) => {
		switch ( event.action ) {
			case 'roll':
				return $('<span>').text( ` rolled ${ event.result } ` );
			case 'flip':
				return $('<span>').text( ` flipped the table! Nobody can roll!` );
			case 'fix':
				return $('<span>').text( ` fixed the table! Keep rolling!` );
		}
		return '';
	};

	const processResponse = ( response ) => {
		$results.empty();
		let max = -1;
		let winner = '';
		const lastHour = Date.now() / 1000 - 3600;
		if ( ! response.results.length ) {
			$results.append( $( '<li>' ).text( 'No rolls yet!' ) );
		} else {
			response.results.forEach( ( r ) => {
				if ( 'roll' === r.action && r.time > lastHour && Number( r.result ) > max ) {
					max = Number( r.result );
					winner = r.name;
				}
				$results.append(
					$( '<li>' ).append(
						$('<span>')
							.css( {
								'color': '#ddd',
								'font-size': 'small',
								'margin-right': '5px',
							} )
							.text( formatDate( r.time ) ),
						$('<span>')
							.css( {
								'font-weight': 'bold',
								'color': stringToColor( r.name ),
							} )
							.text( unescapeHtml( r.name ) ),
						renderAction( r )
					)
				);
			} );
			if ( 'flip' === response.results[ 0 ].action ) {
				disableButtons();
				$fixBtn.removeAttr( 'disabled' );
			} else {
				enableButtons();
				$fixBtn.attr( 'disabled', 'disabled' );
			}
		}
		if ( -1 !== max ) {
			$leader.text( winner + ' 🎉' );
		} else {
			$leader.text( 'n/a' );
		}
		lastRender++;
		if ( lastRender > 1000 ) {
			lastRender = 0;
		}
	};

	const postAction = ( actionType ) => $.ajax( {
		type: 'POST',
		url: actionUrl,
		dataType: 'json',
		contentType: 'application/json',
		data: JSON.stringify( {
			action: 'hydra_roller_ajax',
			userId,
			actionType,
		} ),
	} );

	const poll = () => {
		if ( acting ) {
			setTimeout( poll, 1000 );
			return;
		}
		const expectedRender = lastRender;
		$.ajax( {
			type: 'POST',
			url: historyUrl,
			dataType: 'json',
			contentType: 'application/json',
		} )
			.then( ( response ) => {
				expectedRender === lastRender && processResponse( response )
				setTimeout( poll, 1000 );
			} );
	};

	const disableButtons = () => {
		$btn.attr( 'disabled', 'disabled' );
		$flipBtn.attr( 'disabled', 'disabled' );
		$fixBtn.attr( 'disabled', 'disabled' );
	};

	const enableButtons = () => {
		$btn.removeAttr( 'disabled' );
		$flipBtn.removeAttr( 'disabled' );
		$fixBtn.removeAttr( 'disabled' );
	};

	$btn.click( () => {
		if ( acting ) {
			return;
		}
		disableButtons();
		acting = true;
		postAction( 'roll' )
			.then( ( response ) => {
				processResponse( response );
				acting = false;
			} )
			.fail( ( error ) => {
				console.log( error );
				acting = false;
			} );
	} );

	$flipBtn.click( () => {
		if ( acting ) {
			return;
		}
		disableButtons();
		acting = true;
		postAction( 'flip' )
			.then( ( response ) => {
				processResponse( response );
				acting = false;
			} )
			.fail( ( error ) => {
				console.log( error );
				acting = false;
			} );
	} );

	$fixBtn.click( () => {
		if ( acting ) {
			return;
		}
		disableButtons();
		acting = true;
		postAction( 'fix' )
			.then( ( response ) => {
				processResponse( response );
				acting = false;
			} )
			.fail( ( error ) => {
				console.log( error );
				acting = false;
			} );
	} );

	poll();
} );
