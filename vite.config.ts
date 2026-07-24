import { defineConfig } from 'vite';
import { resolve } from 'path';
import { mkdirSync, writeFileSync } from 'fs';

import nette from '@nette/vite-plugin';
import tailwindcss from '@tailwindcss/vite'
import FullReload from 'vite-plugin-full-reload';


const ddevPrimaryUrl = (
	process.env.DDEV_PRIMARY_URL_WITHOUT_PORT
	?? process.env.DDEV_PRIMARY_URL
	?? 'https://bestapp.ddev.site'
).replace(/\/$/, '');

const vitePublicOrigin = `${ddevPrimaryUrl}:5173`;
const vitePublicHost = new URL(vitePublicOrigin).hostname;

function forceNetteDevServerOrigin(publicOrigin: string) {
	return {
		name: 'force-nette-dev-server-origin',
		apply: 'serve' as const,
		enforce: 'post' as const,
		configureServer(server: any) {
			if (!server.httpServer) {
				return;
			}

			server.httpServer.on('listening', () => {
				let infoFilePath = resolve(server.config.build.outDir, '.vite/nette.json');
				mkdirSync(resolve(infoFilePath, '..'), { recursive: true });
				writeFileSync(infoFilePath, JSON.stringify({ devServer: publicOrigin }, null, '\t'));
				server.config.server.origin = publicOrigin;
			});
		},
	};
}



export default defineConfig({
	resolve: {
			alias: {
				'@': resolve(__dirname, 'assets/js'),
				'~': resolve(__dirname, 'node_modules'),
			},
		},
	plugins: [
		nette({
			entry: ['main.js', 'admin.js'],
		}),
		forceNetteDevServerOrigin(vitePublicOrigin),
		FullReload([
			'app/**/*.latte',
			'app/**/*.php',
		]),
		tailwindcss(),
	],

	build: {
		emptyOutDir: true,
	},

	css: {
		devSourcemap: true,
	},

  // Adjust Vites dev server for DDEV
  // https://vitejs.dev/config/server-options.html
  server: {
    // Respond to all network requests
    host: `0.0.0.0`,
    port: 5173,
    strictPort: true,
	allowedHosts: ['.ddev.site'],
		// Defines the public origin used for generated dev asset URLs.
		// Keep this stable to avoid `http://0.0.0.0` leaking into font/image URLs.
		origin: vitePublicOrigin,
		hmr: {
			protocol: vitePublicOrigin.startsWith('https://') ? 'wss' : 'ws',
			host: vitePublicHost,
			clientPort: 5173,
		},
    // Configure CORS securely for the Vite dev server to allow requests
    // from *.ddev.site domains, supports additional hostnames (via regex).
    // If you use another `project_tld`, adjust this value accordingly.
    cors: {
      origin: /https?:\/\/([A-Za-z0-9\-\.]+)?(\.ddev\.site)(?::\d+)?$/,
    },
  },
});
