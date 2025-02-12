<template>
	<v-dialog v-model="modelValue" width="900" scrollable>
		<v-stepper v-model="step" flat v-slot="{ prev, next }">
			<VCard>
				<VCardTitle>
					<h2>Sync Orders to Xero</h2>
					<v-stepper-header>
						<v-stepper-item :value="1"> Filter Orders </v-stepper-item>
						<v-divider></v-divider>
						<v-stepper-item :value="2"> Select Orders </v-stepper-item>
						<v-divider></v-divider>
						<v-stepper-item :value="3"> Confirm </v-stepper-item>
					</v-stepper-header>
				</VCardTitle>
				<VCardText style="max-height: 65vh; overflow: auto">
					<v-stepper-window>
						<v-stepper-window-item :value="1">
							<v-card flat>
								<v-card-text class="px-0">
									<AuiSetting
										title="Date Range"
										help="Select a range of order dates that will be processed. No range searches all
												orders."
									>
										<AuiInputDateRangePicker
											v-model="filters.date"
											label="Date Range"
											@input="selectedOrders = []"
										/>
									</AuiSetting>
									<AuiSetting title="Included Statuses">
										<template #help>
											<p class="mb-4">Choose the order statuses that will be included</p>
											<VBtn block color="success" variant="tonal" @click="selectAllStatuses"
												>Select All</VBtn
											>
										</template>
										<v-row no-gutters>
											<v-col v-for="status in statuses" :key="status.id" cols="12" sm="6" md="6">
												<v-checkbox
													v-model="filters.statuses"
													:value="status.id"
													:color="status.colour"
													hide-details
													class="my-0"
													@update:model-value="selectedOrders = []"
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
						</v-stepper-window-item>
						<v-stepper-window-item :value="2">
							<v-card flat>
								<v-data-table
									v-if="availableOrders.length > 0"
									v-model="selectedOrders"
									:items="availableOrders"
									:headers="[
										{ text: 'ID', value: 'id' },
										{ text: 'Account', value: 'account.name' },
										{ text: 'User', value: 'user.full_name' },
										{ text: 'Date', value: 'created_at' },
									]"
									return-object
									show-select
								>
									<template #item.created_at="{ item }">{{ mediumDate(item.created_at) }}</template>
								</v-data-table>
								<VEmptyState
									v-else
									icon="mdi-database-off"
									title="No Available Orders"
									text="No orders matched your selected filters. Go back and select again."
								/>
							</v-card>
						</v-stepper-window-item>
						<v-stepper-window-item :value="3">
							<v-card flat>
								<v-card-text>
									You are about to sync {{ selectedOrders.length }} orders to Xero.
									<v-list density="compact">
										<v-list-item v-for="order in selectedOrders" :key="order.id">
											<template #prepend>
												<v-avatar color="#336633" size="50">
													<div class="font-weight-bold text-uppercase d-flex flex-column">
														<div>{{ order.lines?.length }}</div>
														<div class="text-caption">items</div>
													</div>
												</v-avatar>
											</template>
											<v-list-item-title v-if="order.account">{{
												order.account.name
											}}</v-list-item-title>
											<v-list-item-subtitle>{{
												mediumDate(order.created_at)
											}}</v-list-item-subtitle>
											<v-list-item-action>
												<small>Total:</small>
												{{ currency(order.cart_inc_tax) }}
											</v-list-item-action>
										</v-list-item>
									</v-list>
								</v-card-text>
							</v-card>
						</v-stepper-window-item>
					</v-stepper-window>
				</VCardText>
				<VCardActions style="background-color: #efefef; border-top: 1px solid #ccc">
					<v-btn v-if="step > 1" color="error" variant="text" @click="prev"> Back </v-btn>
					<v-btn v-else color="error" variant="text" @click="modelValue = false"> Close </v-btn>
					<VSpacer />
					<v-btn v-if="step === 1" color="primary" :loading="loadingAvailable" @click="loadAvailableOrders()"
						>Next</v-btn
					>
					<v-btn v-else-if="step === 2" color="primary" @click="next">Next</v-btn>
					<v-btn
						v-else
						color="primary"
						:disabled="selectedOrders.length === 0"
						:loading="isSyncing"
						@click="doSync"
					>
						Sync Selected Orders
					</v-btn>
				</VCardActions>
			</VCard>
		</v-stepper>
	</v-dialog>
</template>

<script setup>
import { ref, axios, useRoute, reactive, computed, usePage, mediumDate, currency, router, watch } from "adminui";

defineOptions({
	name: "OrderSyncFlow",
});

const route = useRoute();
const modelValue = defineModel({
	type: Boolean,
	default: false,
});

const statuses = computed(() => usePage().props.orderStatuses);

const filters = reactive({
	date: [],
	statuses: statuses.value.filter((item) => ["completed", "paid"].includes(item.ref)).map((item) => item.id),
});

const step = ref(1);
const availableOrders = ref([]);
const loadingAvailable = ref(false);
const loadAvailableOrders = async () => {
	loadingAvailable.value = true;
	const result = await axios.post(route("admin.setup.integrations.xero.orders.search"), filters);
	availableOrders.value = result.data.data;
	step.value++;
	loadingAvailable.value = false;
};

const selectAllStatuses = () => {
	filters.statuses = statuses.value.map((status) => status.id);
};

const selectedOrders = ref([]);

const isSyncing = ref(false);
const doSync = async () => {
	isSyncing.value = true;
	router.post(
		route("admin.setup.integrations.xero.orders.sync"),
		{
			orders: selectedOrders.value.map((order) => order.id),
		},
		{
			onSuccess() {
				isSyncing.value = false;
				selectedOrders.value = [];
				filters.date = [];
				emit("input", false);

				step.value = 1;
			},
		}
	);
};
</script>
