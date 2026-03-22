( function () {
	const DESKTOP_BREAKPOINT = 782;
	const ITEM_SELECTOR = '.wp-block-navigation-item.has-child';
	const SUBMENU_SELECTOR = ':scope > .wp-block-navigation__submenu-container';

	function resetHorizontalScrollIfNeeded() {
		if ( document.documentElement.scrollWidth <= window.innerWidth + 1 && window.scrollX !== 0 ) {
			window.scrollTo( { left: 0, top: window.scrollY, behavior: 'instant' } );
		}
	}

	function clearPlacement( submenu ) {
		if ( ! submenu ) {
			return;
		}

		submenu.style.removeProperty( 'left' );
		submenu.style.removeProperty( 'right' );
		submenu.style.removeProperty( 'inset-inline-start' );
		submenu.style.removeProperty( 'inset-inline-end' );
	}

	function measureSubmenuWidth( submenu ) {
		if ( ! submenu ) {
			return 0;
		}

		const liveWidth = submenu.getBoundingClientRect().width;
		if ( liveWidth > 2 ) {
			return liveWidth;
		}

		const clone = submenu.cloneNode( true );
		clone.setAttribute( 'aria-hidden', 'true' );
		clone.style.setProperty( 'position', 'absolute', 'important' );
		clone.style.setProperty( 'left', '-99999px', 'important' );
		clone.style.setProperty( 'right', 'auto', 'important' );
		clone.style.setProperty( 'top', '0', 'important' );
		clone.style.setProperty( 'bottom', 'auto', 'important' );
		clone.style.setProperty( 'visibility', 'hidden', 'important' );
		clone.style.setProperty( 'opacity', '1', 'important' );
		clone.style.setProperty( 'display', 'flex', 'important' );
		clone.style.setProperty( 'width', 'auto', 'important' );
		clone.style.setProperty( 'height', 'auto', 'important' );
		clone.style.setProperty( 'overflow', 'visible', 'important' );
		clone.style.setProperty( 'inset-inline-start', 'auto', 'important' );
		clone.style.setProperty( 'inset-inline-end', 'auto', 'important' );
		document.body.appendChild( clone );

		const width = clone.getBoundingClientRect().width;
		clone.remove();

		return width;
	}

	function queuePositionSubmenu( item ) {
		window.requestAnimationFrame( () => {
			window.requestAnimationFrame( () => {
				positionSubmenu( item );
			} );
		} );
	}

	function measureDirectChildSubmenuWidth( submenu ) {
		if ( ! submenu ) {
			return 0;
		}

		let maxWidth = 0;
		submenu
			.querySelectorAll( ':scope > li.has-child' )
			.forEach( ( childItem ) => {
				const childSubmenu = childItem.querySelector( ':scope > .wp-block-navigation__submenu-container' );
				const fallbackWidth = childItem.getBoundingClientRect().width + 2;
				maxWidth = Math.max( maxWidth, fallbackWidth, measureSubmenuWidth( childSubmenu ) );
			} );

		return maxWidth;
	}

	function setInlineStart( submenu, value ) {
		submenu.style.setProperty( 'left', value, 'important' );
		submenu.style.setProperty( 'right', 'auto', 'important' );
		submenu.style.setProperty( 'inset-inline-start', value, 'important' );
		submenu.style.setProperty( 'inset-inline-end', 'auto', 'important' );
	}

	function setInlineEnd( submenu, value ) {
		submenu.style.setProperty( 'right', value, 'important' );
		submenu.style.setProperty( 'left', 'auto', 'important' );
		submenu.style.setProperty( 'inset-inline-end', value, 'important' );
		submenu.style.setProperty( 'inset-inline-start', 'auto', 'important' );
	}

	function positionSubmenu( item ) {
		const submenu = item.querySelector( SUBMENU_SELECTOR );
		if ( ! submenu ) {
			return;
		}

		if ( window.innerWidth < DESKTOP_BREAKPOINT ) {
			clearPlacement( submenu );
			return;
		}

		const itemRect = item.getBoundingClientRect();
		const submenuWidth = Math.max( measureSubmenuWidth( submenu ), itemRect.width + 2 );
		const childSubmenuWidth = measureDirectChildSubmenuWidth( submenu );
		if ( submenuWidth <= 0 ) {
			clearPlacement( submenu );
			return;
		}

		const availableRight = window.innerWidth - itemRect.right;
		const availableLeft = itemRect.left;
		const openRight = availableRight >= submenuWidth || availableRight > availableLeft;
		const nested = item.parentElement && item.parentElement.classList.contains( 'wp-block-navigation__submenu-container' );

		if ( nested ) {
			if ( openRight ) {
				setInlineStart( submenu, 'calc(100% - 1px)' );
			} else {
				setInlineEnd( submenu, 'calc(100% - 1px)' );
			}

			return;
		}

		if ( openRight ) {
			let leftOffset = -1;

			if ( childSubmenuWidth > 0 ) {
				const projectedRight = itemRect.left + leftOffset + submenuWidth;
				const reservedRightSpace = window.innerWidth - projectedRight;

				if ( reservedRightSpace < childSubmenuWidth ) {
					const shiftLeft = Math.min( childSubmenuWidth - reservedRightSpace, itemRect.left + leftOffset );
					leftOffset -= shiftLeft;
				}
			}

			setInlineStart( submenu, `${ leftOffset }px` );
		} else {
			setInlineEnd( submenu, '-1px' );
		}
	}

	function bindItem( item ) {
		item.addEventListener( 'pointerenter', () => queuePositionSubmenu( item ) );
		item.addEventListener( 'focusin', () => queuePositionSubmenu( item ) );
		item.addEventListener( 'click', () => queuePositionSubmenu( item ) );
	}

	function init() {
		document.querySelectorAll( ITEM_SELECTOR ).forEach( bindItem );
		window.requestAnimationFrame( resetHorizontalScrollIfNeeded );
		window.addEventListener( 'resize', () => {
			resetHorizontalScrollIfNeeded();
			document.querySelectorAll( ITEM_SELECTOR ).forEach( ( item ) => {
				positionSubmenu( item );
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init, { once: true } );
	} else {
		init();
	}
}() );
