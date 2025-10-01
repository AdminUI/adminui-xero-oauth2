import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import AdminUI from "vite-plugin-adminui";

export default defineConfig(({ mode }) => {
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
		],
		build: {
			emptyOutDir: true,
			outDir: "./publish/js",
		},
	};
});
