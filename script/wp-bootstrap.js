#!/usr/bin/env node
/**
 * Bootstraps WordPress in docker-compose environment using separate wpcli service:
 *  - Brings up containers
 *  - Waits for WP to respond
 *  - Installs (if needed) via wp-cli container
 *  - Activates plugin & sets permalinks
 */
const { execSync } = require('node:child_process');
require('dotenv').config();

const adminUser = process.env.WP_ADMIN_USER || 'admin';
const adminPass = process.env.WP_ADMIN_PASS || 'password';
const adminEmail = process.env.WP_ADMIN_EMAIL || 'admin@example.com';
const siteUrl = process.env.WP_BASE_URL || 'http://localhost:8088';
const title = process.env.WP_SITE_TITLE || 'WPDR E2E';

function sh(cmd) {
	console.log('> ' + cmd);
	execSync(cmd, { stdio: 'inherit' });
}

function wp(cmd) {
	// Use run to ensure a fresh transient container with wp binary.
	sh(`docker compose run --rm wpcli ${cmd}`);
}

(async () => {
	try {
		sh('docker compose up -d');

		// Wait for HTTP 200 or 302 on /wp-login.php
		let ready = false;
		for (let i = 0; i < 30; i++) {
			try {
				// Fresh installs often redirect (302); existing installs may return 200.
				execSync(
					`curl -s -o /dev/null -w "%{http_code}" ${siteUrl}/wp-login.php | grep -E '^(302|200)$'`
				);
				ready = true;
				break;
			} catch {
				await new Promise((r) => setTimeout(r, 2000));
			}
		}
		if (!ready) {
			throw new Error('WordPress not responding on /wp-login.php');
		}

		// Check if already installed (exit code non-zero => not installed)
		let installed = true;
		try {
			wp('core is-installed');
		} catch (err) {
			installed = false;
		}

		if (!installed) {
			wp(
				`core install --url='${siteUrl}' --title='${title}' --admin_user='${adminUser}' --admin_password='${adminPass}' --admin_email='${adminEmail}' --skip-email`
			);
		}

		// Activate plugin (idempotent)
		wp('plugin activate wp-document-revisions || true');

		// Pretty permalinks
		wp(`rewrite structure '/%postname%/' --hard`);
		wp('rewrite flush --hard');

		console.log('Bootstrap complete.');
	} catch (e) {
		console.error(e);
		process.exit(1);
	}
})();
