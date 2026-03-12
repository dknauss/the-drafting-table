import { defineConfig, devices } from '@playwright/test';

const wpEnvPort = process.env.WP_ENV_PORT || '8888';
const baseURL = process.env.PLAYWRIGHT_BASE_URL || `http://localhost:${ wpEnvPort }`;

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
		baseURL,
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
