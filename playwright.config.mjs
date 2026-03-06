import { defineConfig, devices } from '@playwright/test';

export default defineConfig( {
	testDir: './tests/smoke',
	timeout: 30_000,
	expect: {
		timeout: 5_000,
	},
	fullyParallel: true,
	reporter: [
		[ 'list' ],
		[ 'html', { open: 'never' } ],
	],
	use: {
		baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:8888',
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
	},
	projects: [
		{
			name: 'chromium',
			use: {
				...devices['Desktop Chrome'],
			},
		},
	],
} );
