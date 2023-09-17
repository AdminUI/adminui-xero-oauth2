<template>
	<v-dialog :value="props.value" width="900" @input="emit('input', $event)" scrollable>
		<AuiCard title="Sync Orders to Xero">
			<v-stepper v-model="step" alt-labels flat>
				<v-stepper-header>
					<v-stepper-step step="1"> Filter Orders </v-stepper-step>
					<v-divider></v-divider>
					<v-stepper-step step="2"> Select Orders </v-stepper-step>
					<v-divider></v-divider>
					<v-stepper-step step="3"> Confirm </v-stepper-step>
				</v-stepper-header>
				<v-stepper-items>
					<v-stepper-content step="1">
						<v-card flat>
							<v-card-text class="px-0">
								<AuiSetting
									title="Date Range"
									help="Select a range of order dates that will be processed. No range searches all
											orders."
								>
									<AuiInputDateRangePicker v-model="filters.date" label="Date Range" />
								</AuiSetting>
								<AuiSetting
									title="Included Statuses"
									help="Choose the order statuses that will be included"
								>
									<v-row no-gutters>
										<v-col v-for="status in statuses" cols="12" sm="6" md="6">
											<v-checkbox
												v-model="filters.statuses"
												:value="status.id"
												:color="status.colour"
												hide-details
												class="mt-0 mb-2"
											>
												<template #label>
													<span class="text-capitalize">{{ status.name }}</span>
												</template>
											</v-checkbox>
										</v-col>
									</v-row>
								</AuiSetting>
							</v-card-text>
						</v-card>
						<div class="d-flex justify-space-between">
							<v-btn color="error" text @click="emit('input', false)"> Cancel </v-btn>
							<v-btn color="primary" text @click="loadAvailableOrders"> Next </v-btn>
						</div>
					</v-stepper-content>
					<v-stepper-content step="2">
						<v-card flat>
							<v-data-table
								v-model="selectedOrders"
								:items="availableOrders"
								:headers="[
									{ text: 'ID', value: 'id' },
									{ text: 'Account', value: 'account.name' },
									{ text: 'User', value: 'user.full_name' },
									{ text: 'Date', value: 'created_at' }
								]"
								show-select
							>
								<template #item.created_at="{ item }">{{ mediumDate(item.created_at) }}</template>
							</v-data-table>
						</v-card>
						<div class="d-flex justify-space-between">
							<v-btn text @click="step--"> Back </v-btn>
							<v-btn color="primary" @click="step++"> Next </v-btn>
						</div>
					</v-stepper-content>
					<v-stepper-content step="3">
						<v-card flat>
							<v-card-text>
								You are about to sync {{ selectedOrders.length }} orders to Xero.
								<v-list dense>
									<v-list-item v-for="order in selectedOrders">
										<v-list-item-avatar color="#33663333" size="50" style="font-size: 0.7rem">
											<small class="font-weight-bold text-uppercase"
												>{{ order.lines.length }}<br />items</small
											>
										</v-list-item-avatar>
										<v-list-item-content>
											<v-list-item-title>{{ order.account.name }}</v-list-item-title>
											<v-list-item-subtitle>{{
												mediumDate(order.created_at)
											}}</v-list-item-subtitle>
										</v-list-item-content>
										<v-list-item-action>
											<small>Total:</small>
											{{ currency(order.cart_inc_tax) }}
										</v-list-item-action>
									</v-list-item>
								</v-list>
							</v-card-text>
						</v-card>
						<div class="d-flex justify-space-between">
							<v-btn text @click="step--"> Back </v-btn>
							<v-btn color="primary" :loading="isSyncing" @click="doSync"> Sync Selected Orders </v-btn>
						</div>
					</v-stepper-content>
				</v-stepper-items>
			</v-stepper>
		</AuiCard>
	</v-dialog>
</template>

<script setup>
import { ref, axios, useRoute, reactive, computed, usePage, mediumDate, currency, router } from "adminui";

const route = useRoute();
const emit = defineEmits(["input"]);
const props = defineProps({
	value: {
		type: Boolean,
		default: false
	}
});

const statuses = computed(() => usePage().props.orderStatuses);

const filters = reactive({
	date: [],
	statuses: statuses.value.filter((item) => ["completed", "paid"].includes(item.ref)).map((item) => item.id)
});

const step = ref(1);
const availableOrders = ref([]);

const loadAvailableOrders = async () => {
	const result = await axios.post(route("admin.setup.integrations.xero.orders.search"), filters);
	availableOrders.value = result.data.data;
	step.value++;
};

const selectedOrders = ref([]);

const isSyncing = ref(false);
const doSync = async () => {
	isSyncing.value = true;
	router.post(
		route("admin.setup.integrations.xero.orders.sync"),
		{
			orders: selectedOrders.value.map((order) => order.id)
		},
		{
			onSuccess() {
				isSyncing.value = false;
				selectedOrders.value = [];
				filters.date = [];
				emit("input", false);

				step.value = 1;
			}
		}
	);
};
</script>
