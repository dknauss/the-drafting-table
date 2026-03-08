import { expect, test } from '@playwright/test';

test.describe( 'The Drafting Table smoke suite', () => {
	async function loginToAdmin( page ) {
		await page.goto( '/wp-admin/' );

		if ( page.url().includes( '/wp-login.php' ) ) {
			await page.locator( '#user_login' ).fill( 'admin' );
			await page.locator( '#user_pass' ).fill( 'password' );
			await Promise.all( [
				page.waitForURL( ( url ) => ! url.pathname.includes( '/wp-login.php' ), { timeout: 15000 } ),
				page.locator( '#wp-submit' ).click(),
			] );
		}
	}

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
		await page.goto( '/journal/' );

		await expect( page.getByText( /From the Journal/i ) ).toBeVisible();
		const journalCardCount = await page.locator( '.journal-card' ).count();
		expect( journalCardCount ).toBeGreaterThanOrEqual( 3 );
		await expect( page.locator( '.journal-card .wp-block-post-title a' ).first() ).toBeVisible();
	} );

	test( 'single post renders title and featured image', async ( { page } ) => {
		await page.goto( '/glass-transparency-dissolution-walls/' );

		await expect( page.getByRole( 'heading', { level: 1, name: /Glass, Transparency, and the Dissolution of Walls/i } ) ).toBeVisible();
		await expect( page.getByRole( 'img', { name: /transparent glass wall facing landscape/i } ) ).toBeVisible();
	} );

	test( 'archive template renders archive heading and post cards', async ( { page } ) => {
		await page.goto( '/category/material-studies/' );

		await expect( page.getByRole( 'heading', { level: 1, name: /Category:\s*Material Studies/i } ) ).toBeVisible();
		await expect( page.locator( '.journal-card' ).first() ).toBeVisible();
	} );

	test( 'a11y fixture content from theme test data imports correctly', async ( { page } ) => {
		await page.goto( '/lorem-ipsum/' );

		await expect( page.getByRole( 'heading', { level: 1, name: /Lorem Ipsum/i } ) ).toBeVisible();
	} );

	test( 'search template renders results for imported test content', async ( { page } ) => {
		await page.goto( '/?s=glass' );

		await expect( page.getByRole( 'heading', { level: 1, name: /Search results for:/i } ) ).toBeVisible();
		await expect( page.locator( '.wp-block-post-title a' ).first() ).toBeVisible();
	} );

	test( '404 template renders fallback content and site navigation', async ( { page } ) => {
		await page.goto( '/__drafting-table-route-that-does-not-exist__/' );

		await expect( page.getByRole( 'heading', { level: 1, name: /Sheet Not on File/i } ) ).toBeVisible();
		await expect( page.locator( 'header nav[aria-label*="Main navigation"]' ).first() ).toBeVisible();
	} );

	test( 'index fallback preview keeps the footer outside main', async ( { page } ) => {
		await page.goto( '/__drafting-table/index-preview/' );
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
} );
