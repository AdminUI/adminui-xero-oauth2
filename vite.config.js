import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import AdminUI from "vite-plugin-adminui";
import { resolve } from "node:path";
import viteBasicSslPlugin from "@vitejs/plugin-basic-ssl";

export default defineConfig(({ mode }) => {
	const env = loadEnv(mode, resolve(process.env.PWD, "../../../"), "VITE");

	return {
		plugins: [
			laravel({
				input: "./resources/index.js",
				publicDirectory: "publish/js",
				hotFile: "publish/js/hot",
				buildDirectory: "vendor/adminui-xero-oauth2",
			}),
			vue({
				template: {
					transformAssetUrls: {
						base: ".",
						includeAbsolute: false,
					},
				},
			}),
			AdminUI({ mode }),
			env.VITE_HTTPS ? viteBasicSslPlugin() : undefined,
		],
		build: {
			emptyOutDir: true,
			outDir: "./publish/js",
		},
		server:
			mode === "development"
				? {
						host: env.VITE_SERVER_HOST,
						cors: true,
				  }
				: {},
	};
});
