import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue2";
import AdminUI from "vite-plugin-adminui";
import { resolve } from "node:path";
import * as dotenv from "dotenv";
import { homedir } from "node:os";

export default defineConfig(({ mode }) => {
	const env = loadEnv(mode, "../../");
	return {
		plugins: [
			laravel({
				input: "./resources/index.js",
				publicDirectory: "publish/js",
				hotFile: "publish/js/hot"
			}),
			vue({
				template: {
					transformAssetUrls: {
						base: ".",
						includeAbsolute: false
					}
				}
			}),
			AdminUI()
		],
		build: {
			emptyOutDir: true,
			outDir: "./publish/js"
		},
		server:
			mode === "development"
				? {
						host: env.VITE_DEV_SERVER_HOST
				  }
				: {}
	};
});
