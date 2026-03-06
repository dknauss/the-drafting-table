import { expect, test } from '@playwright/test';

test.describe( 'The Drafting Table smoke suite', () => {
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

	test( 'journal page renders featured and archive entries with demo media', async ( { page } ) => {
		await page.goto( '/journal/' );

		await expect( page.getByRole( 'heading', { level: 1, name: /The Journal/i } ) ).toBeVisible();
		await expect( page.getByText( 'Featured Entry' ) ).toBeVisible();
		await expect( page.getByRole( 'heading', { name: /Glass, Transparency, and the Dissolution of Walls/i } ) ).toBeVisible();
		await expect( page.getByRole( 'img', { name: /transparent glass wall facing landscape/i } ) ).toBeVisible();
		await expect( page.locator( '.journal-card' ) ).toHaveCount( 7 );
	} );

	test( 'index fallback preview keeps the footer outside main', async ( { page } ) => {
		await page.goto( '/__drafting-table/index-preview/' );

		await expect( page.locator( 'header nav[aria-label="Main navigation"]' ) ).toBeVisible();
		await expect( page.locator( 'footer nav[aria-label="Footer navigation"]' ) ).toBeVisible();
		await expect( page.getByRole( 'heading', { level: 1, name: /Where Structure Meets/i } ) ).toBeVisible();
		await expect( page.locator( 'main footer' ) ).toHaveCount( 0 );
	} );
} );
