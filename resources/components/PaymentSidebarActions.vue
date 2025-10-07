<template>
	<VListItem v-if="showSendPayment" prepend-icon="mdi-cloud-arrow-up" @click="onSendPayment"
		>Send Payment to Xero</VListItem
	>
	<VListItem v-else> Payment already synced to Xero </VListItem>
</template>

<script setup>
import { computed, router, useRoute } from "adminui";

const props = defineProps({
	integrations: {
		type: Array,
		default: () => [],
	},
	payment: {
		type: Object,
		default: () => ({}),
	},
});
const route = useRoute();

const showSendPayment = computed(() => !props.integrations.find((item) => item.type === "xero"));

const onSendPayment = () => {
	router.post(route("admin.setup.integrations.xero.payments.sync"), {
		payment_id: props.payment.id,
	});
};
</script>
