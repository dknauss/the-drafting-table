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

	async function resolveHref( locator ) {
		const href = await locator.getAttribute( 'href' );
		expect( href ).toBeTruthy();
		return href;
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

	test( 'home template renders featured and archive entries with demo media', async ( { page } ) => {
		await page.goto( '/?the_drafting_table_preview_template=home' );

		await expect( page ).toHaveURL( /the_drafting_table_preview_template=home/ );
		await expect( page.getByRole( 'heading', { level: 1 } ).first() ).toBeVisible();
		const journalCardCount = await page.locator( '.journal-card' ).count();
		expect( journalCardCount ).toBeGreaterThanOrEqual( 3 );
		await expect( page.locator( '.journal-card .wp-block-post-title a' ).first() ).toBeVisible();
	} );

	test( 'single post renders title and featured image', async ( { page } ) => {
		await page.goto( '/' );
		const postLink = page.getByRole( 'link', { name: 'Glass, Transparency, and the Dissolution of Walls' } ).first();
		await expect( postLink ).toBeVisible();

		const postHref = await resolveHref( postLink );
		await page.goto( postHref );

		await expect( page.getByRole( 'heading', { level: 1, name: /Glass, Transparency, and the Dissolution of Walls/i } ) ).toBeVisible();
		await expect( page.getByRole( 'img', { name: /transparent glass wall facing landscape/i } ) ).toBeVisible();
	} );

	test( 'archive template renders archive heading and post cards', async ( { page } ) => {
		await page.goto( '/?the_drafting_table_preview_template=home' );
		const archiveLink = page.locator( '.journal-card .wp-block-post-terms a' ).first();
		await expect( archiveLink ).toBeVisible();

		const archiveHref = await resolveHref( archiveLink );
		await page.goto( archiveHref );

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
		await page.goto( '/' );
		const featuredPostLink = page.getByRole( 'link', { name: 'Glass, Transparency, and the Dissolution of Walls' } ).first();
		await expect( featuredPostLink ).toBeVisible();
		const featuredPostHref = await resolveHref( featuredPostLink );
		await page.goto( '/wp-admin/themes.php' );

		await Promise.all( [
			page.waitForURL( /the_drafting_table_demo=removed/ ),
			removeDemoLink.click(),
		] );

		await page.goto( '/wp-admin/options-reading.php' );
		await expect( page.getByRole( 'radio', { name: /Your latest posts/i } ) ).toBeChecked();

		const removedPostResponse = await page.goto( featuredPostHref );
		expect( removedPostResponse?.status() ).toBe( 404 );

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

		const restoredPostResponse = await page.goto( featuredPostHref );
		expect( restoredPostResponse?.status() ).toBe( 200 );
		await expect(
			page.getByRole( 'heading', { level: 1, name: /Glass, Transparency, and the Dissolution of Walls/i } )
		).toBeVisible();
	} );

	test( 'critical accessibility checks pass across templates and imported fixtures', async ( { page } ) => {
		const staticRoutes = [
			'/',
			'/?the_drafting_table_preview_template=home',
			'/?s=glass',
			'/?pagename=__drafting-table-route-that-does-not-exist__',
			'/?pagename=lorem-ipsum',
			'/?pagename=keyboard-navigation',
		];

		for ( const routePath of staticRoutes ) {
			await assertNoCriticalA11yViolations( page, routePath );
		}

		await page.goto( '/' );
		const singleHref = await resolveHref(
			page.getByRole( 'link', { name: 'Glass, Transparency, and the Dissolution of Walls' } ).first()
		);
		await assertNoCriticalA11yViolations( page, singleHref );

		await page.goto( '/?the_drafting_table_preview_template=home' );
		const archiveHref = await resolveHref( page.locator( '.journal-card .wp-block-post-terms a' ).first() );
		await assertNoCriticalA11yViolations( page, archiveHref );
	} );
} );
