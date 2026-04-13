import { expect, test } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

test.describe( 'The Drafting Table smoke suite', () => {
	test.describe.configure( { mode: 'serial' } );

	async function loginToUser( page, username, password ) {
		await page.goto( '/wp-login.php?action=logout' );
		if ( await page.getByRole( 'link', { name: /log out/i } ).count() ) {
			await Promise.all( [
				page.waitForURL( /wp-login\.php\?loggedout=true/ ),
				page.getByRole( 'link', { name: /log out/i } ).click(),
			] );
		}

		await page.goto( '/wp-login.php' );
		await page.locator( '#user_login' ).fill( username );
		await page.locator( '#user_pass' ).fill( password );
		await Promise.all( [
			page.waitForURL( ( url ) => ! url.pathname.includes( '/wp-login.php' ), { timeout: 15000 } ),
			page.locator( '#wp-submit' ).click(),
		] );
	}

	async function loginToAdmin( page ) {
		await loginToUser( page, 'admin', 'password' );
	}

	async function ensureDemoInstalled( page ) {
		await loginToAdmin( page );
		await page.goto( '/wp-admin/themes.php' );

		const removeDemoLink = page.getByRole( 'link', { name: /Remove Demo Content/i } ).first();
		if ( ( await removeDemoLink.count() ) > 0 ) {
			return;
		}

		const installDemoLink = page.getByRole( 'link', { name: /Install Demo Content/i } ).first();
		if ( ( await installDemoLink.count() ) > 0 ) {
			await Promise.all( [
				page.waitForURL( /the_drafting_table_demo=installed/ ),
				installDemoLink.click(),
			] );
		}
	}

	function formatAxeViolations( violations ) {
		return violations
			.map(
				( violation ) =>
					`${ violation.id }: ${ violation.help } (${ violation.nodes.length } node${ violation.nodes.length === 1 ? '' : 's' })`
			)
			.join( '\n' );
	}

	async function openFeaturedSinglePost( page ) {
		await page.goto( '/?the_drafting_table_smoke_post=featured' );
	}

	async function openMaterialStudiesArchive( page ) {
		await page.goto( '/?category_name=material-studies' );
	}

	async function openDesktopNestedSubmenus( page ) {
		await page.setViewportSize( { width: 1440, height: 1000 } );
		await page.goto( '/?pagename=level-1/level-2/level-3' );

		const headerNav = page.locator( 'header nav[aria-label*="Main navigation"]' ).first();
		const topLevelLink = headerNav.locator( 'a[href$="/level-1/"]' ).first();
		const topLevelItem = topLevelLink.locator( 'xpath=ancestor::li[contains(@class,"has-child")]' ).last();
		const topLevelSubmenu = topLevelItem.locator( ':scope > .wp-block-navigation__submenu-container' );

		await expect( topLevelLink ).toBeVisible();
		await topLevelLink.hover();
		await expect( topLevelSubmenu ).toBeVisible();

		const subLink = topLevelSubmenu.locator( 'a[href$="/level-1/level-2/"]' ).first();
		const subItem = subLink.locator( 'xpath=ancestor::li[contains(@class,"has-child")]' ).last();
		const subSubmenu = subItem.locator( ':scope > .wp-block-navigation__submenu-container' );

		await expect( subLink ).toBeVisible();
		await subLink.hover();
		await expect( subSubmenu ).toBeVisible();

		return { headerNav, topLevelSubmenu, subSubmenu };
	}

	function horizontalCenterOffset( box, viewportWidth ) {
		return Math.abs( box.x + box.width / 2 - viewportWidth / 2 );
	}

	async function getOutlineWidth( locator ) {
		return locator.evaluate( ( element ) => Number.parseFloat( getComputedStyle( element ).outlineWidth ) || 0 );
	}

	async function assertNoCriticalA11yViolations( page, routePath ) {
		await page.goto( routePath );

		const results = await new AxeBuilder( { page } ).analyze();
		const criticalViolations = results.violations.filter( ( violation ) => 'critical' === violation.impact );

		expect(
			criticalViolations,
			`Critical accessibility violations on ${ routePath }:\n${ formatAxeViolations( criticalViolations ) }`
		).toEqual( [] );
	}

	test.beforeEach( async ( { page } ) => {
		await ensureDemoInstalled( page );
	} );

	test( 'front page renders hero media and portable navigation', async ( { page } ) => {
		await page.goto( '/' );

		const headerNav = page.locator( 'header nav[aria-label*="Main navigation"]' ).first();
		const footerNav = page.locator( 'footer nav[aria-label*="Footer navigation"]' ).first();

		await expect( headerNav ).toBeVisible();
		await expect( footerNav ).toBeVisible();
		await expect( headerNav ).toContainText( /Projects/i );
		await expect( headerNav ).toContainText( /Journal/i );
		await expect( page.getByRole( 'heading', { level: 1, name: /Where Structure Meets/i } ) ).toBeVisible();
		await expect( page.getByRole( 'link', { name: 'Glass, Transparency, and the Dissolution of Walls' } ).first() ).toBeVisible();
		await expect( page.getByRole( 'img', { name: /transparent glass wall facing landscape/i } ) ).toBeVisible();
	} );

	test( 'skip link becomes visible on focus and jumps to the main content target', async ( { page } ) => {
		await page.goto( '/' );

		const skipLink = page.locator( '.skip-link' ).first();
		await skipLink.focus();

		await expect( skipLink ).toBeFocused();
		expect( await getOutlineWidth( skipLink ) ).toBeGreaterThan( 0 );

		const skipLinkBox = await skipLink.boundingBox();
		expect( skipLinkBox ).not.toBeNull();
		expect( skipLinkBox.y ).toBeLessThan( 12 );

		await page.keyboard.press( 'Enter' );
		await expect.poll( () => page.evaluate( () => window.location.hash ) ).toBe( '#wp--skip-link--target' );
		await expect.poll( () => page.evaluate( () => window.scrollY ) ).toBeGreaterThan( 0 );
		await expect( page.locator( '#wp--skip-link--target' ) ).toHaveCount( 1 );
	} );

	test( 'home template renders featured and archive entries with demo media', async ( { page } ) => {
		await page.goto( '/?the_drafting_table_preview_template=home' );

		await expect( page ).toHaveURL( /the_drafting_table_preview_template=home/ );
		await expect( page.getByRole( 'heading', { level: 1 } ).first() ).toBeVisible();
		const journalCardCount = await page.locator( '.journal-card' ).count();
		expect( journalCardCount ).toBeGreaterThanOrEqual( 3 );
		await expect( page.locator( '.journal-card .wp-block-post-title a' ).first() ).toBeVisible();
	} );

	test( 'single post renders title and featured image', async ( { page } ) => {
		await openFeaturedSinglePost( page );

		await expect( page.getByRole( 'heading', { level: 1, name: /Glass, Transparency, and the Dissolution of Walls/i } ) ).toBeVisible();
		await expect( page.getByRole( 'img', { name: /transparent glass wall facing landscape/i } ) ).toBeVisible();
	} );

	test( 'archive template renders archive heading and post cards', async ( { page } ) => {
		await openMaterialStudiesArchive( page );

		await expect( page.getByRole( 'heading', { level: 1, name: /Category:/i } ) ).toBeVisible();
		await expect( page.locator( '.journal-card' ).first() ).toBeVisible();
	} );

	test( 'a11y fixture content from theme test data imports correctly', async ( { page } ) => {
		await page.goto( '/?pagename=lorem-ipsum' );

		await expect( page.getByRole( 'heading', { level: 1, name: /Lorem Ipsum/i } ) ).toBeVisible();
	} );

	test( 'search template renders results for imported test content', async ( { page } ) => {
		await page.goto( '/?s=glass' );

		await expect( page.getByRole( 'heading', { level: 1, name: /Search results for:/i } ) ).toBeVisible();
		await expect( page.locator( '.wp-block-post-title a' ).first() ).toBeVisible();
	} );

	test( '404 template renders fallback content and site navigation', async ( { page } ) => {
		await page.goto( '/?pagename=__drafting-table-route-that-does-not-exist__' );

		await expect( page.getByRole( 'heading', { level: 1, name: /Sheet Not on File/i } ) ).toBeVisible();
		await expect( page.locator( 'header nav[aria-label*="Main navigation"]' ).first() ).toBeVisible();
	} );

	test( 'index fallback preview keeps the footer outside main', async ( { page } ) => {
		await page.goto( '/?the_drafting_table_preview_template=index' );
		await expect( page.locator( 'header nav[aria-label*="Main navigation"]' ).first() ).toBeVisible();
		await expect( page.locator( 'footer nav[aria-label*="Footer navigation"]' ).first() ).toBeVisible();
		await expect( page.locator( 'main footer' ) ).toHaveCount( 0 );
	} );

	test( 'desktop nested header submenu stays clear of its parent and footer navigation stays flat', async ( { page } ) => {
		const { topLevelSubmenu, subSubmenu } = await openDesktopNestedSubmenus( page );
		const footerNav = page.locator( 'footer nav[aria-label*="Footer navigation"]' ).first();
		const viewportWidth = page.viewportSize().width;
		const parentBox = await topLevelSubmenu.boundingBox();
		const subBox = await subSubmenu.boundingBox();

		expect( parentBox ).not.toBeNull();
		expect( subBox ).not.toBeNull();

		const overlapTolerance = 2;
		const opensAwayFromParent =
			subBox.x + subBox.width <= parentBox.x + overlapTolerance ||
			subBox.x >= parentBox.x + parentBox.width - overlapTolerance;

		expect( opensAwayFromParent ).toBe( true );
		expect( subBox.x ).toBeGreaterThanOrEqual( -1 );
		expect( subBox.x + subBox.width ).toBeLessThanOrEqual( viewportWidth + 1 );
		await expect( footerNav.locator( '.has-child' ) ).toHaveCount( 0 );
	} );

	test( 'responsive menu keeps nested items centered while resizing an open overlay', async ( { page } ) => {
		await page.setViewportSize( { width: 400, height: 900 } );
		await page.goto( '/?pagename=level-1/level-2/level-3' );

		await page.locator( 'header .wp-block-navigation__responsive-container-open' ).click();

		const responsiveContainer = page.locator(
			'header .wp-block-navigation__responsive-container.is-menu-open'
		);
		const journalLink = responsiveContainer.getByRole( 'link', { name: /^Level 1$/i } ).first();
		const subTwoLink = responsiveContainer.getByRole( 'link', { name: /^Level 2$/i } ).first();

		await expect( responsiveContainer ).toBeVisible();
		await expect( journalLink ).toBeVisible();
		await expect( subTwoLink ).toBeVisible();

		let viewportWidth = page.viewportSize().width;
		let journalBox = await journalLink.boundingBox();
		let subTwoBox = await subTwoLink.boundingBox();

		expect( horizontalCenterOffset( journalBox, viewportWidth ) ).toBeLessThan( 80 );
		expect( horizontalCenterOffset( subTwoBox, viewportWidth ) ).toBeLessThan( 80 );

		await page.setViewportSize( { width: 1144, height: 900 } );
		await page.waitForTimeout( 150 );

		viewportWidth = page.viewportSize().width;
		journalBox = await journalLink.boundingBox();
		subTwoBox = await subTwoLink.boundingBox();

		expect( horizontalCenterOffset( journalBox, viewportWidth ) ).toBeLessThan( 80 );
		expect( horizontalCenterOffset( subTwoBox, viewportWidth ) ).toBeLessThan( 80 );
		expect( await page.evaluate( () => window.scrollX ) ).toBe( 0 );
	} );

	test( 'responsive menu is keyboard operable and closes with Enter', async ( { page } ) => {
		await page.setViewportSize( { width: 400, height: 900 } );
		await page.goto( '/' );

		const openButton = page.locator( 'header .wp-block-navigation__responsive-container-open' ).first();
		await openButton.focus();

		await expect( openButton ).toBeFocused();

		await page.keyboard.press( 'Enter' );

		const responsiveContainer = page.locator(
			'header .wp-block-navigation__responsive-container.is-menu-open'
		);
		const closeButton = responsiveContainer.locator( '.wp-block-navigation__responsive-container-close' ).first();

		await expect( responsiveContainer ).toBeVisible();
		await expect( closeButton ).toBeVisible();

		await closeButton.focus();
		await expect( closeButton ).toBeFocused();

		await page.keyboard.press( 'Enter' );
		await expect( responsiveContainer ).toBeHidden();
		await expect( openButton ).toBeVisible();
	} );

	test( 'reduced-motion preference disables front-page entry animations', async ( { page } ) => {
		await page.emulateMedia( { reducedMotion: 'reduce' } );
		await page.goto( '/' );

		const heroHeadingGroup = page.locator( '.fade-in-up' ).first();
		const heroMediaGroup = page.locator( '.fade-in-up-delay-1' ).first();

		await expect( heroHeadingGroup ).toBeVisible();
		await expect( heroMediaGroup ).toBeVisible();

		for ( const locator of [ heroHeadingGroup, heroMediaGroup ] ) {
			const styles = await locator.evaluate( ( element ) => {
				const computed = getComputedStyle( element );
				return {
					animationName: computed.animationName,
					animationDuration: computed.animationDuration,
					animationDurationSeconds: Number.parseFloat( computed.animationDuration ) || 0,
					opacity: computed.opacity,
				};
			} );

			expect( styles.animationName ).toBe( 'none' );
			expect( styles.animationDurationSeconds ).toBeLessThan( 0.001 );
			expect( styles.opacity ).toBe( '1' );
		}
	} );

	test( 'installer UI exposes companion demo management action', async ( { page } ) => {
		await loginToAdmin( page );
		await page.goto( '/wp-admin/themes.php' );

		await expect( page.getByText( /Demo content tools|Install Demo Content/i ) ).toBeVisible();
		await expect( page.getByRole( 'link', { name: /Remove Demo Content|Install Demo Content/i } ) ).toBeVisible();
	} );

	test( 'installer actions are hidden and blocked for editor role', async ( { page } ) => {
		await loginToUser( page, 'editor', 'password' );
		await page.goto( '/wp-admin/themes.php' );

		await expect( page.getByRole( 'link', { name: /Remove Demo Content|Install Demo Content/i } ) ).toHaveCount( 0 );

		await page.goto( '/wp-admin/admin-post.php?action=the_drafting_table_install_demo' );
		await expect( page.getByText( /You do not have permission to perform this action/i ) ).toBeVisible();
	} );

	test( 'installer behavior removes and reinstalls demo content with setting rollback', async ( { page } ) => {
		await loginToAdmin( page );
		await page.goto( '/wp-admin/themes.php' );

		const removeDemoLink = page.getByRole( 'link', { name: /Remove Demo Content/i } ).first();
		const installDemoLink = page.getByRole( 'link', { name: /Install Demo Content/i } ).first();

		if ( ( await removeDemoLink.count() ) === 0 ) {
			await expect( installDemoLink ).toBeVisible();
			await Promise.all( [
				page.waitForURL( /the_drafting_table_demo=installed/ ),
				installDemoLink.click(),
			] );
			await page.goto( '/wp-admin/themes.php' );
		}

		await expect( removeDemoLink ).toBeVisible();

		await Promise.all( [
			page.waitForURL( /the_drafting_table_demo=removed/ ),
			removeDemoLink.click(),
		] );

		await page.goto( '/wp-admin/options-reading.php' );
		await expect( page.getByRole( 'radio', { name: /Your latest posts/i } ) ).toBeChecked();

		await page.goto( '/wp-admin/themes.php' );
		await expect( installDemoLink ).toBeVisible();
		await Promise.all( [
			page.waitForURL( /the_drafting_table_demo=installed/ ),
			installDemoLink.click(),
		] );

		await page.goto( '/wp-admin/options-reading.php' );
		await expect( page.getByRole( 'radio', { name: /A static page/i } ) ).toBeChecked();
		await expect( page.getByLabel( /Homepage:/i ) ).not.toHaveValue( '0' );
		await expect( page.getByLabel( /Posts page:/i ) ).not.toHaveValue( '0' );

		await page.goto( '/?the_drafting_table_preview_template=home' );
		const journalCardCount = await page.locator( '.journal-card' ).count();
		expect( journalCardCount ).toBeGreaterThanOrEqual( 3 );
	} );

	for ( const { label, routePath } of [
		{ label: 'front page', routePath: '/' },
		{ label: 'home preview template', routePath: '/?the_drafting_table_preview_template=home' },
		{ label: 'search results', routePath: '/?s=glass' },
		{ label: '404 fallback', routePath: '/?pagename=__drafting-table-route-that-does-not-exist__' },
		{ label: 'lorem ipsum fixture', routePath: '/?pagename=lorem-ipsum' },
		{ label: 'keyboard navigation fixture', routePath: '/?pagename=keyboard-navigation' },
	] ) {
		test( `critical accessibility checks pass on ${ label }`, async ( { page } ) => {
			await assertNoCriticalA11yViolations( page, routePath );
		} );
	}

	test( 'critical accessibility checks pass on the featured single post', async ( { page } ) => {
		await openFeaturedSinglePost( page );
		const results = await new AxeBuilder( { page } ).analyze();
		const criticalViolations = results.violations.filter( ( violation ) => 'critical' === violation.impact );

		expect(
			criticalViolations,
			`Critical accessibility violations on featured single post:\n${ formatAxeViolations( criticalViolations ) }`
		).toEqual( [] );
	} );

	test( 'critical accessibility checks pass on the material studies archive', async ( { page } ) => {
		await openMaterialStudiesArchive( page );
		const results = await new AxeBuilder( { page } ).analyze();
		const criticalViolations = results.violations.filter( ( violation ) => 'critical' === violation.impact );

		expect(
			criticalViolations,
			`Critical accessibility violations on material studies archive:\n${ formatAxeViolations( criticalViolations ) }`
		).toEqual( [] );
	} );
} );
