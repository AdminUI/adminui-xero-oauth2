console.log("Loading xero addons");
window.auiAddons.addNamespace("xero", import.meta.glob(`./pages/**/*.vue`, { eager: false }));
