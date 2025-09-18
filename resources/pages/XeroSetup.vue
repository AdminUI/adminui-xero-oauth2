<template>
	<v-row>
		<v-col cols="9">
			<VCard class="pb-4 relative">
				<div class="px-4 max-w-prose">
					<v-slide-y-transition>
						<div v-if="!form.xero_linked_account && props.xeroStatus.connected">
							<v-alert type="warning" border="start">
								Please select an account to link for your integration
							</v-alert>
						</div>
					</v-slide-y-transition>
				</div>
				<AuiSetting
					title="Sync Orders"
					help="When enabled, orders placed through AdminUI will be automatically pushed to the linked Xero account"
				>
					<v-switch
						v-model="form.xero_sync_orders"
						label="Sync Orders"
						:disabled="!props.xeroStatus.connected || !form.xero_linked_account"
					/>
				</AuiSetting>
				<VDivider />
				<AuiSetting
					title="Sync Contact Updates"
					help="While contacts will initially be pushed when syncing orders, you can also choose to push changes to your accounts to Xero to update linked contacts"
				>
					<v-switch
						v-model="form.xero_sync_contacts"
						label="Sync Contacts"
						:disabled="!props.xeroStatus.connected || !form.xero_linked_account"
					/>
				</AuiSetting>
				<VDivider />
				<AuiSetting
					title="Sync Payments"
					help="When enabled, orders placed through AdminUI will be automatically pushed to the linked Xero account"
				>
					<v-switch
						v-model="form.xero_sync_payments"
						label="Sync Payments"
						:disabled="!props.xeroStatus.connected || !form.xero_linked_account"
					/>
				</AuiSetting>
				<VDivider />
				<AuiSetting
					title="Use Xero Account Balance"
					help="Instead of using the AdminUI ledger, get account balance from linked Xero customer account"
				>
					<v-switch
						v-model="form.xero_use_account_balance"
						label="Use Xero Account Balance"
						:disabled="!props.xeroStatus.connected || !form.xero_linked_account"
					/>
				</AuiSetting>
				<VDivider />
				<AuiSetting title="Sync Payment Methods" help="Select which payments should be sent to Xero">
					<AuiInputSelect
						v-model="form.xero_sync_payment_methods"
						:error="formErrors.xero_sync_payment_methods"
						label="Included Methods"
						:items="paymentMethods"
						item-title="description"
						multiple
						chips
						:dense="false"
						deletable-chips
						:disabled="!form.xero_sync_payments"
					/>
				</AuiSetting>
				<VDivider />

				<AuiSetting title="App Credentials">
					<template #help>
						To enable AdminUI to connect with your Xero account, you must create an app and then enter its
						credentials here.
						<a href="https://developer.xero.com/app/manage" target="_blank">Create Xero Application</a>
					</template>
					<AuiInputText
						v-model="form.xero_client_id"
						label="Client ID"
						:error="formErrors.xero_client_id"
						class="max-w-prose"
					/>
					<AuiInputPassword
						v-model="form.xero_client_secret"
						label="Client Secret"
						:error="formErrors.xero_client_secret"
						class="max-w-prose"
					/>
				</AuiSetting>
				<VDivider />

				<AuiSetting
					title="Linked Xero Account"
					help="Select the account on your connected Xero account with which you want to sync."
				>
					<AuiInputAutocomplete
						v-model="form.xero_linked_account"
						:error="formErrors.xero_client_secret"
						label="Account"
						:items="props.xeroStatus.accounts?.Accounts"
						item-title="Name"
						item-value="AccountID"
						clearable
						class="max-w-prose"
					/>
				</AuiSetting>
				<VDivider />

				<AuiSetting
					title="Webhooks"
					help="To enable AdminUI to respond to events from your Xero account, you must provide a webhook key"
				>
					<AuiInputPassword v-model="form.xero_webhook_key" class="max-w-prose" label="Webhook Key" />
				</AuiSetting>
				<VDivider />
				<AuiSetting
					title="Your Xero App URLs"
					help="Xero requires you to register a few URLs with them for full integration. The first is the URL to use when a connection attempt is complete. The second is the URL to send Webhook deliveries."
				>
					<v-table show-hover>
						<tbody>
							<tr>
								<td>Redirect URL:</td>
								<td>
									<div class="d-flex align-center">
										<strong>{{ props.xeroCallback }}</strong>
										<v-btn
											v-if="isSupported"
											variant="text"
											icon="mdi-content-copy"
											size="small"
											class="ml-2"
											@click="copy()"
										/>
									</div>
								</td>
							</tr>
							<tr>
								<td>Webhook Delivery URL:</td>
								<td>
									<div class="d-flex align-center">
										<strong>{{ props.xeroWebhookDeliveryURL }}</strong>
										<v-btn
											v-if="isSupported"
											variant="text"
											icon="mdi-content-copy"
											size="small"
											class="ml-2"
											@click="copy(props.xeroWebhookDeliveryURL)"
										/>
									</div>
								</td>
							</tr>
						</tbody>
					</v-table>
				</AuiSetting>
				<div class="px-4">
					<v-divider class="my-4" />
				</div>
				<div class="xero-logo d-flex px-4 absolute top-4 right-0">
					<XeroLogo style="height: 100px" />
				</div>
			</VCard>
		</v-col>
		<v-col cols="3">
			<AuiCard title="Connection Status" class="mb-8">
				<template v-if="props.xeroStatus.error">
					<v-alert type="error">{{ props.xeroStatus.error }}</v-alert>
					<v-btn color="primary" block :href="route('xero.auth.authorize')">Reconnect to Xero</v-btn>
				</template>
				<template v-else-if="props.xeroStatus.connected">
					<p class="text-center text-h6">
						<v-icon class="mr-4" color="success" icon="mdi-flash" />
						Connected
					</p>
					<p class="text-center">
						Connected as <strong>{{ props.xeroStatus.organisationName }}</strong> via
						{{ props.xeroStatus.username }}
					</p>
					<v-btn variant="text" color="primary" block :href="route('xero.auth.authorize')"
						>Reconnect to Xero</v-btn
					>
				</template>
				<template v-else>
					<p class="text-center text-h6 mb-2">
						<v-icon class="mr-4" icon="mdi-flash-off" />
						Not Connected
					</p>
					<v-btn color="primary" block :href="route('xero.auth.authorize')">Connect to Xero</v-btn>
				</template>
			</AuiCard>
			<AuiCard title="Actions" class="my-8" content-class="px-0">
				<v-list>
					<v-list-item @click.stop="showOrderSyncFlow = true">
						<template #prepend>
							<v-icon>mdi-book-sync</v-icon>
						</template>

						<v-list-item-title>Manually Sync Orders</v-list-item-title>
					</v-list-item>
					<v-list-item
						:disabled="!props.xeroStatus.connected || props.failedOrderSyncs.length === 0"
						@click.stop="showFailedSyncsFlow = true"
					>
						<template #prepend>
							<v-icon color="error">mdi-sync-alert</v-icon>
						</template>

						<v-list-item-title>
							<span>View Failed Order Syncs</span>
						</v-list-item-title>
						<template #append>
							<v-badge
								:content="props.failedOrderSyncs.length"
								location="right"
								inline
								class="mt-0"
								color="error"
								:model-value="props.failedOrderSyncs.length > 0"
							/>
						</template>
					</v-list-item>
					<v-list-item
						:disabled="!props.xeroStatus.connected || props.failedPaymentSyncs.length === 0"
						@click.stop="showFailedPaymentSyncsFlow = true"
					>
						<template #prepend>
							<v-icon color="error">mdi-cash-sync</v-icon>
						</template>

						<v-list-item-title>
							<span>View Failed Payment Syncs</span>
						</v-list-item-title>
						<template #append>
							<v-badge
								:content="props.failedPaymentSyncs.length"
								location="right"
								inline
								class="mt-0"
								color="error"
								:model-value="props.failedPaymentSyncs.length > 0"
							/>
						</template>
					</v-list-item>
				</v-list>
			</AuiCard>
		</v-col>
		<OrderSyncFlow v-model="showOrderSyncFlow" />
		<FailedOrderSyncs v-model="showFailedSyncsFlow" :items="props.failedOrderSyncs" />
		<FailedPaymentSyncs v-model="showFailedPaymentSyncsFlow" :items="props.failedPaymentSyncs" />
	</v-row>
</template>

<script setup>
import { useClipboard } from "@vueuse/core";
import { useApiForm, useRoute, ref, watch, useSnackbar } from "adminui";
import XeroLogo from "../components/XeroLogo.vue";
import OrderSyncFlow from "../components/OrderSyncFlow.vue";
import FailedOrderSyncs from "../components/FailedOrderSyncs.vue";
import FailedPaymentSyncs from "../components/FailedPaymentSyncs.vue";

defineOptions({
	inheritAttrs: false,
});

const route = useRoute();
const snackbar = useSnackbar();
const props = defineProps({
	xeroCallback: {
		type: String,
		default: "",
	},
	xeroWebhookDeliveryURL: {
		type: String,
		default: "",
	},
	xeroSettings: {
		type: Array,
		default: () => [],
	},
	paymentMethods: {
		type: Array,
		default: () => [],
	},
	xeroStatus: {
		type: Object,
		default: () => ({}),
	},
	failedOrderSyncs: {
		type: Array,
		default: () => [],
	},
	failedPaymentSyncs: {
		type: Array,
		default: () => [],
	},
});

const { copy, copied, isSupported } = useClipboard({ source: () => props.xeroCallback });
watch(copied, (v) => {
	if (v) {
		snackbar.add({
			type: "success",
			title: "Copied",
			text: "URL was successfully copied to clipboard",
		});
	}
});

const getInitialData = () => {
	return props.xeroSettings.reduce((acc, curr) => {
		const value = curr.value_cast === "integer" ? +curr.value : curr.value;
		acc[curr.name] = value;
		return acc;
	}, {});
};

let { form, formErrors } = useApiForm({
	route: "admin.setup.site-preferences.update-value",
	initialData: getInitialData(),
});

const showOrderSyncFlow = ref(false);

/* *********************************************
 * Failed Syncs
 * ******************************************* */
const showFailedSyncsFlow = ref(false);
const showFailedPaymentSyncsFlow = ref(false);
</script>
