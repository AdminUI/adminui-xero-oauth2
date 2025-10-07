<template>
	<VDialog v-model="show" width="600">
		<template #activator="{ props: activatorProps }">
			<VBtn
				v-bind="activatorProps"
				v-tooltip="`Re-sync this payment to Xero`"
				color="warning"
				variant="text"
				icon="mdi-cloud-refresh-outline"
			/>
		</template>
		<VCard title="Confirm payment re-sync to Xero">
			<VCardText>
				<VAlert border="start" type="warning" prominent>
					<p class="mb-2">
						To re-sync this payment to Xero, it will first be deleted and then sent as a new payment.
					</p>
					<p>Be aware that if the new payment fails to send, then the order may be left missing payments.</p>
				</VAlert>
			</VCardText>
			<VCardActions>
				<VBtn @click="show = false">Cancel</VBtn>
				<VSpacer />
				<VBtn color="error" @click="doResync">Confirm Re-Sync</VBtn>
			</VCardActions>
		</VCard>
	</VDialog>
</template>

<script setup>
import { useRoute, ref, router } from "adminui";

const props = defineProps({
	item: {
		type: Object,
		default: () => ({}),
	},
});

const route = useRoute();
const show = ref(false);
const doResync = () => {
	router.put(
		route("admin.setup.integrations.xero.payments.resync"),
		{
			order_integration_id: props.item.id,
		},
		{
			onSuccess() {
				show.value = false;
			},
		}
	);
};
</script>
