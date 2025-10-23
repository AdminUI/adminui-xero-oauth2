<template>
	<VListItem v-if="showSendOrder" prepend-icon="mdi-cloud-arrow-up" @click="onSendOrder"
		>Send Order to Xero</VListItem
	>
	<VListItem v-else> Order already synced to Xero </VListItem>
</template>

<script setup>
import { computed, router, useRoute } from "adminui";

const props = defineProps({
	integrations: {
		type: Array,
		default: () => [],
	},
	order: {
		type: Object,
		default: () => ({}),
	},
});
const route = useRoute();

const showSendOrder = computed(
	() => !props.integrations.find((item) => item.type === "xero" && item.model_type === "order")
);

const onSendOrder = () => {
	router.post(route("admin.setup.integrations.xero.orders.sync"), {
		orders: [props.order.id],
	});
};
</script>
