import { defineConfig } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';
import { createRequire } from 'module';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );
const require = createRequire( import.meta.url );

const BASE_URL = 'http://localhost:8888';
const STORAGE_STATE_PATH = path.resolve(
    __dirname,
    'tests/e2e/config/.auth/admin.json'
);

process.env.STORAGE_STATE_PATH = STORAGE_STATE_PATH;
process.env.WP_BASE_URL = BASE_URL;

export default defineConfig( {
    globalTimeout: 600_000,
    testDir: './tests/e2e/specs',
    outputDir: './tests/e2e/results',
    fullyParallel: true,
    forbidOnly: !! process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 2 : 3,
    reporter: process.env.CI ? 'github' : 'list',
    globalSetup: require.resolve( './tests/e2e/config/global-setup.js' ),
    use: {
        baseURL: BASE_URL,
        storageState: STORAGE_STATE_PATH,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { browserName: 'chromium' },
        },
    ],
} );
