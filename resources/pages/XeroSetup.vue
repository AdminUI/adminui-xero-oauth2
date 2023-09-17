<template>
	<v-card class="mt-4">
		<v-row>
			<v-col cols="9">
				<div class="xero-logo d-flex px-4">
					<XeroLogo />
				</div>
				<AuiSetting
					title="Integration Settings"
					help="When enabled, orders placed through AdminUI will be automatically pushed to the linked Xero account"
				>
					<v-switch v-model="form.xero_enabled" label="Sync" :disabled="!props.xeroStatus.connected" />
				</AuiSetting>
				<v-divider />
				<AuiSetting title="App Credentials">
					<template #help>
						To enable AdminUI to connect with your Xero account, you must create an app and then enter its
						credentials here.
						<a href="https://developer.xero.com/app/manage" target="_blank">Create Xero Application</a>
					</template>
					<AuiInputText v-model="form.xero_client_id" label="Client ID" />
					<AuiInputPassword v-model="form.xero_client_secret" label="Client Secret" />
				</AuiSetting>
				<AuiSetting
					title="Webhooks"
					help="To enable AdminUI to respond to events from your Xero account, you must provide a webhook key"
				>
					<AuiInputPassword v-model="form.xero_webhook_key" label="Webhook Key" />
				</AuiSetting>
				<div class="px-4">
					<v-divider class="my-4" />
				</div>
				<AuiSetting
					title="Your Xero App URLs"
					help="Xero requires you to register a few URLs with them for full integration. The first is the URL to use when a connection attempt is complete. The second is the URL to send Webhook deliveries."
				>
					<v-simple-table show-hover>
						<tbody>
							<tr>
								<td>Redirect URL:</td>
								<td>
									<strong>{{ props.xeroCallback }}</strong>
									<v-btn v-if="isSupported" icon small class="ml-2" @click="copy()">
										<v-icon small>mdi-content-copy</v-icon>
									</v-btn>
								</td>
							</tr>
							<tr>
								<td>Webhook Delivery URL:</td>
								<td>
									<strong>{{ props.xeroWebhookDeliveryURL }}</strong>
									<v-btn
										v-if="isSupported"
										icon
										small
										class="ml-2"
										@click="copy(props.xeroWebhookDeliveryURL)"
									>
										<v-icon small>mdi-content-copy</v-icon>
									</v-btn>
								</td>
							</tr>
						</tbody>
					</v-simple-table>
				</AuiSetting>
				<div class="px-4">
					<v-divider class="my-4" />
				</div>
				<AuiSetting
					title="Xero Settings"
					help="These settings control the interactions between AdminUI and Xero"
				>
					<AuiInputText
						v-model="form.xero_reference_prefix"
						label="Reference Prefix"
						hint="When creating invoices, this will be prefixed to the Xero order reference"
					/>
				</AuiSetting>
			</v-col>
			<v-col cols="3">
				<AuiCard title="Connection Status" class="mb-8">
					<template v-if="props.xeroStatus.error">
						<v-alert type="error">{{ props.xeroStatus.error }}</v-alert>
						<v-btn color="primary" block :href="route('xero.auth.authorize')">Reconnect to Xero</v-btn>
					</template>
					<template v-else-if="props.xeroStatus.connected">
						<p class="text-center text-h6">
							<v-icon class="mr-4" color="success">mdi-flash</v-icon>Connected
						</p>
						<p class="text-center">
							Connected as <strong>{{ props.xeroStatus.organisationName }}</strong> via
							{{ props.xeroStatus.username }}
						</p>
						<v-btn text color="primary" block :href="route('xero.auth.authorize')">Reconnect to Xero</v-btn>
					</template>
					<template v-else>
						<p class="text-center text-h6"><v-icon class="mr-4">mdi-flash-off</v-icon>Not Connected</p>
						<v-btn color="primary" block :href="route('xero.auth.authorize')">Connect to Xero</v-btn>
					</template>
				</AuiCard>
				<AuiCard title="Actions" class="my-8">
					<v-list>
						<v-list-item @click.stop="showOrderSyncFlow = true">
							<v-list-item-icon>
								<v-icon>mdi-book-sync</v-icon>
							</v-list-item-icon>
							<v-list-item-content>
								<v-list-item-title>Manually Sync Orders</v-list-item-title>
							</v-list-item-content>
						</v-list-item>
						<v-list-item
							:disabled="props.failedOrderSyncs.length === 0"
							@click.stop="showFailedSyncsFlow = true"
						>
							<v-list-item-icon>
								<v-icon color="error">mdi-sync-alert</v-icon>
							</v-list-item-icon>
							<v-list-item-content>
								<v-list-item-title>
									<v-badge
										:content="props.failedOrderSyncs.length"
										overlap
										right
										inline
										class="mt-0"
										color="error"
										:value="props.failedOrderSyncs.length > 0"
									>
										<span>View Failed Order Syncs</span>
									</v-badge>
								</v-list-item-title>
							</v-list-item-content>
						</v-list-item>
					</v-list>
				</AuiCard>
			</v-col>
		</v-row>
		<OrderSyncFlow v-model="showOrderSyncFlow" />
		<FailedOrderSyncs v-model="showFailedSyncsFlow" :items="props.failedOrderSyncs" />
	</v-card>
</template>

<script setup>
import { useClipboard } from "@vueuse/core";
import { useApiForm, useRoute, ref } from "adminui";
import XeroLogo from "../components/XeroLogo.vue";
import OrderSyncFlow from "../components/OrderSyncFlow.vue";
import FailedOrderSyncs from "../components/FailedOrderSyncs.vue";

const route = useRoute();
const props = defineProps({
	xeroCallback: {
		type: String,
		default: ""
	},
	xeroWebhookDeliveryURL: {
		type: String,
		default: ""
	},
	xeroSettings: {
		type: Array,
		default: () => []
	},
	xeroStatus: {
		type: Object,
		default: () => ({})
	},
	failedOrderSyncs: {
		type: Array,
		default: () => []
	}
});

const { copy, copied, isSupported } = useClipboard({ source: () => props.xeroCallback });

const getInitialData = () => {
	return props.xeroSettings.reduce((acc, curr) => {
		const value = curr.value_cast === "integer" ? +curr.value : curr.value;
		acc[curr.name] = value;
		return acc;
	}, {});
};

let { form, formErrors } = useApiForm({ route: "admin.api.config.preferences", initialData: getInitialData() });

const showOrderSyncFlow = ref(false);

/* *********************************************
 * Failed Syncs
 * ******************************************* */
const showFailedSyncsFlow = ref(false);
</script>

<style scoped>
.xero-logo {
	height: 100px;
}
</style>