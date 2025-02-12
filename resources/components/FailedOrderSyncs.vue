<template>
	<v-dialog
		:model-value="props.value"
		width="1400"
		@update:model-value="emit('update:modelValue', $event)"
		scrollable
	>
		<AuiCard title="Failed Order Syncs">
			<v-data-table
				v-model="selected"
				show-select
				:items="failedJobs"
				:headers="[
					{ text: 'Order ID', value: 'order_id' },
					{ text: 'Account', value: 'order.account.name' },
					{ text: 'User', value: 'order.user.full_name' },
					{ text: 'Order Details', value: 'order' },
					{ text: 'Failure Reason', value: 'exception' },
					{ text: 'Failed at', value: 'failed_at' },
				]"
			>
				<template #item.order_id="{ value }">
					<v-chip
						color="info"
						class="text-white"
						label
						@click="
							copy(value);
							snackbar.add({ text: 'Order ID copied to clipboard', type: 'success' });
						"
					>
						{{ value }}
					</v-chip>
				</template>
				<template #item.order="{ item }">
					<div class="d-flex flex-column">
						<div class="text-caption">{{ item.order.lines.length }} items</div>
						<div class="whitespace-nowrap">
							<span class="font-weight-bold">Subtotal:</span> {{ currency(item.order.cart_exc_tax) }}
						</div>
						<div class="whitespace-nowrap">
							<span class="font-weight-bold">Total:</span> {{ currency(item.order.cart_inc_tax) }}
						</div>
					</div>
				</template>
				<template #item.exception="{ value }">
					<div>{{ value }}</div>
				</template>
				<template #item.failed_at="{ value }">
					<span class="whitespace-nowrap">{{ value }}</span>
				</template>
			</v-data-table>
			<template #actions>
				<v-btn color="error" variant="text" :disabled="selected.length === 0" @click="deleteSelectedJobs"
					>Delete Selected</v-btn
				>
				<v-spacer />
				<v-btn color="primary" :disabled="selected.length === 0" @click="retrySelectedJobs"
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
const emit = defineEmits(["input"]);
const props = defineProps({
	value: {
		type: Boolean,
		default: false,
	},
	items: {
		type: Array,
		required: true,
	},
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
const retrySelectedJobs = () => {
	router.post(
		route("admin.setup.integrations.xero.orders.retry"),
		{
			selected: selected.value.map((item) => item.id),
		},
		{
			onSuccess() {
				emit("input", false);
			},
		}
	);
};

const deleteSelectedJobs = () => {
	router.post(
		route("admin.setup.integrations.xero.orders.delete"),
		{
			selected: selected.value.map((item) => item.id),
		},
		{
			onSuccess() {
				emit("input", false);
			},
		}
	);
};
</script>
