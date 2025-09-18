<template>
	<v-dialog v-model="modelValue" width="1400" scrollable>
		<AuiCard title="Failed Order Syncs">
			<v-data-table
				v-model="selected"
				show-select
				:items="failedJobs"
				:headers="[
					{ title: 'Payment ID', value: 'payment_id' },
					{ title: 'Payment Details', value: 'payment' },
					{ title: 'Failure Reason', value: 'exception', width: '40%' },
					{ title: 'Failed at', value: 'failed_at' },
				]"
			>
				<template #item.payment_id="{ item }">
					<v-chip
						color="info"
						class="text-white"
						label
						@click="
							copy(item.payment.id);
							snackbar.add({ text: 'Payment ID copied to clipboard', type: 'success' });
						"
					>
						{{ item.payment.id }}
					</v-chip>
				</template>
				<template #item.payment="{ value }">
					<VTable hover density="compact">
						<tbody>
							<tr v-if="value.user">
								<td>User</td>
								<td>{{ value.user.full_name }}</td>
							</tr>
							<tr v-if="value.transaction_id">
								<td>Transaction ID</td>
								<td>{{ value.transaction_id }}</td>
							</tr>
							<tr v-if="value.payment_type">
								<td>Payment Type</td>
								<td>{{ value.payment_type }}</td>
							</tr>
							<tr v-if="value.status_details?.text">
								<td>Payment Status</td>
								<td>{{ value.status_details.text }}</td>
							</tr>
							<tr v-if="value.order">
								<td>Order ID</td>
								<td>{{ value.order.id }}</td>
							</tr>
							<tr v-if="value.order">
								<td>Order Invoice ID</td>
								<td>{{ value.order.invoice_id }}</td>
							</tr>
							<tr v-if="value.total">
								<td>Payment Amount</td>
								<td>{{ currency(value.total) }}</td>
							</tr>
						</tbody>
					</VTable>
				</template>
				<template #item.exception="{ value }">
					<div>{{ value }}</div>
				</template>
				<template #item.failed_at="{ value }">
					<span class="whitespace-nowrap">{{ value }}</span>
				</template>
			</v-data-table>
			<template #actions>
				<v-btn
					color="error"
					variant="text"
					:disabled="selected.length === 0"
					:loading="isDeleting"
					@click="deleteSelectedJobs"
					>Delete Selected</v-btn
				>
				<v-spacer />
				<v-btn
					color="primary"
					:disabled="selected.length === 0"
					:loading="isRetrying"
					@click="retrySelectedJobs"
					>Retry Selected</v-btn
				>
			</template>
		</AuiCard>
	</v-dialog>
</template>

<script setup>
import { computed, mediumDate, ref, currency, useSnackbar, router, useRoute } from "adminui";
import { useClipboard } from "@vueuse/core";

const route = useRoute();
const props = defineProps({
	items: {
		type: Array,
		required: true,
	},
});
const modelValue = defineModel({
	type: Boolean,
	default: false,
});

const snackbar = useSnackbar();
const { copy } = useClipboard();

const failedJobs = computed(
	() =>
		props.items.map((item) => {
			if (!item.order) item.order = { lines: [] };
			return {
				...item,
				failed_at: mediumDate(item.failed_at),
				order_id: item?.order?.id ?? null,
			};
		}) ?? []
);

const selected = ref([]);
const isRetrying = ref(false);
const retrySelectedJobs = () => {
	isRetrying.value = true;
	router.post(
		route("admin.setup.integrations.xero.payments.retry"),
		{
			selected: selected.value,
		},
		{
			onSuccess() {
				modelValue.value = false;
			},
			onFinish() {
				isRetrying.value = false;
			},
		}
	);
};

const isDeleting = ref(false);
const deleteSelectedJobs = () => {
	isDeleting.value = true;
	router.post(
		route("admin.setup.integrations.xero.payments.delete"),
		{
			selected: selected.value,
		},
		{
			onSuccess() {
				modelValue.value = false;
				isDeleting.value = true;
			},
			onFinish() {
				isDeleting.value = true;
			},
		}
	);
};
</script>
