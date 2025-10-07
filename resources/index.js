import { defineAsyncComponent } from "vue";

window.auiAddons.addNamespace("xero", import.meta.glob(`./pages/**/*.vue`, { eager: false }));
window.auiAddons.insert(
	{ page: "payment-show", section: "integrations", modifier: "prepend-actions" },
	defineAsyncComponent(() => import("./components/PaymentIntegrationActions.vue")),
	10
);
window.auiAddons.insert(
	{ page: "payment-show", section: "sidebar", modifier: "actions" },
	defineAsyncComponent(() => import("./components/PaymentSidebarActions.vue")),
	10
);
