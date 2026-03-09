import { defineConfig } from 'vite';
import nette from '@nette/vite-plugin';
import tailwindcss from '@tailwindcss/vite'
import FullReload from 'vite-plugin-full-reload';


export default defineConfig({
	plugins: [
		nette({
			entry: 'main.js',
		}),
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
    host: "0.0.0.0",
    port: 5173,
    strictPort: true,
	allowedHosts: ['.ddev.site'],
    // Defines the origin of the generated asset URLs during development,
    // this must be set to the Vite dev server URL and selected port.
    origin: `${process.env.DDEV_PRIMARY_URL_WITHOUT_PORT}:5173`,
    // Configure CORS securely for the Vite dev server to allow requests
    // from *.ddev.site domains, supports additional hostnames (via regex).
    // If you use another `project_tld`, adjust this value accordingly.
    cors: {
      origin: /https?:\/\/([A-Za-z0-9\-\.]+)?(\.ddev\.site)(?::\d+)?$/,
    },
  },
});
