<template>
	<VListItem v-if="showSendOrder" prepend-icon="mdi-cloud-arrow-up" :loading="isSending" @click="onSendOrder"
		>Send Order to Xero</VListItem
	>
	<VListItem v-else> Order already synced to Xero </VListItem>
</template>

<script setup>
import { computed, ref, useRoute, axios } from "adminui";

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

const isSending = ref(false);
const onSendOrder = () => {
	isSending.value = true;
	axios
		.post(route("admin.setup.integrations.xero.orders.sync-synchronous"), {
			orders: [props.order.id],
		})
		.then((res) => {
			console.log(res);
		})
		.finally(() => {
			isSending.value = false;
		});
};
</script>
