import { expect, test } from '@playwright/test';

test.describe( 'The Drafting Table smoke suite', () => {
	async function loginToAdmin( page ) {
		await page.goto( '/wp-login.php' );

		if ( await page.getByLabel( 'Username or Email Address' ).isVisible() ) {
			await page.getByLabel( 'Username or Email Address' ).fill( 'admin' );
			await page.getByLabel( 'Password' ).fill( 'password' );
			await page.getByRole( 'button', { name: 'Log In' } ).click();
		}
	}

	test( 'front page renders hero media and portable navigation', async ( { page } ) => {
		await page.goto( '/' );

		await expect( page.locator( 'header nav[aria-label="Main navigation"]' ) ).toBeVisible();
		await expect( page.locator( 'footer nav[aria-label="Footer navigation"]' ) ).toBeVisible();
		await expect( page.locator( 'header nav[aria-label="Main navigation"]' ) ).toContainText( 'Projects' );
		await expect( page.locator( 'header nav[aria-label="Main navigation"]' ) ).toContainText( 'Journal' );
		await expect( page.getByRole( 'heading', { level: 1, name: /Where Structure Meets/i } ) ).toBeVisible();
		await expect( page.getByRole( 'link', { name: 'Glass, Transparency, and the Dissolution of Walls' } ) ).toBeVisible();
		await expect( page.getByRole( 'img', { name: /transparent glass wall facing landscape/i } ) ).toBeVisible();
	} );

	test( 'home template renders featured and archive entries with demo media', async ( { page } ) => {
		await page.goto( '/journal/' );

		await expect( page.getByRole( 'heading', { level: 1, name: /The Journal/i } ) ).toBeVisible();
		await expect( page.getByText( 'Featured Entry' ) ).toBeVisible();
		await expect( page.getByRole( 'heading', { name: /Glass, Transparency, and the Dissolution of Walls/i } ) ).toBeVisible();
		await expect( page.getByRole( 'img', { name: /transparent glass wall facing landscape/i } ) ).toBeVisible();
		await expect( page.locator( '.journal-card' ) ).toHaveCount( 7 );
	} );

	test( 'single post renders title, featured image, and semantic figcaption', async ( { page } ) => {
		await page.goto( '/glass-transparency-dissolution-walls/' );

		await expect( page.getByRole( 'heading', { level: 1, name: /Glass, Transparency, and the Dissolution of Walls/i } ) ).toBeVisible();
		await expect( page.getByRole( 'img', { name: /transparent glass wall facing landscape/i } ) ).toBeVisible();
		await expect( page.locator( 'figcaption.wp-element-caption' ) ).toContainText( 'Fig. 1' );
	} );

	test( 'archive template renders archive heading and post cards', async ( { page } ) => {
		await page.goto( '/category/material-studies/' );

		await expect( page.getByRole( 'heading', { level: 1 } ) ).toBeVisible();
		await expect( page.locator( '.journal-card' ).first() ).toBeVisible();
	} );

	test( 'a11y fixture content from theme test data imports correctly', async ( { page } ) => {
		await page.goto( '/lorem-ipsum/' );

		await expect( page.getByRole( 'heading', { level: 1, name: /Lorem Ipsum/i } ) ).toBeVisible();
	} );

	test( 'search template renders results for imported test content', async ( { page } ) => {
		await page.goto( '/?s=glass' );

		await expect( page.getByRole( 'heading', { level: 1 } ) ).toBeVisible();
		await expect( page.getByRole( 'link', { name: /Glass, Transparency, and the Dissolution of Walls/i } ) ).toBeVisible();
	} );

	test( '404 template renders fallback content and site navigation', async ( { page } ) => {
		await page.goto( '/__drafting-table-route-that-does-not-exist__/' );

		await expect( page.getByRole( 'heading', { level: 1 } ) ).toBeVisible();
		await expect( page.locator( 'header nav[aria-label="Main navigation"]' ) ).toBeVisible();
	} );

	test( 'index fallback preview keeps the footer outside main', async ( { page } ) => {
		await page.goto( '/__drafting-table/index-preview/' );

		await expect( page.locator( 'header nav[aria-label="Main navigation"]' ) ).toBeVisible();
		await expect( page.locator( 'footer nav[aria-label="Footer navigation"]' ) ).toBeVisible();
		await expect( page.getByRole( 'heading', { level: 1, name: /Where Structure Meets/i } ) ).toBeVisible();
		await expect( page.locator( 'main footer' ) ).toHaveCount( 0 );
	} );

	test( 'installer UI exposes companion demo management action', async ( { page } ) => {
		await loginToAdmin( page );
		await page.goto( '/wp-admin/themes.php' );

		await expect( page.getByText( 'Demo content tools' ) ).toBeVisible();
		await expect( page.getByRole( 'link', { name: 'Remove Demo Content' } ) ).toBeVisible();
	} );
} );
