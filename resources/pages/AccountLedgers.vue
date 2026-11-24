<template>
	<VContainer fluid>
		<VCard>
			<VCardText>
				<DataTable model="ledgers" :route="$route">
					<template #item.name="{ item, value }">
						<VBtn
							:to="$route('admin.account.view', { account: item.id })"
							variant="plain"
							density="compact"
							class="px-0 text-none"
						>
							{{ value }}
						</VBtn>
					</template>
					<template #item.outstanding="{ value }">
						{{ value === null ? "-" : currency(value) }}
					</template>
					<template #item.overdue="{ value }">
						{{ value === null ? "-" : currency(value) }}
					</template>
					<template #item.available_credit="{ value }">
						<span v-if="value === null || value === undefined">-</span>
						<span v-else :class="{ 'text-error': Number(value) < 0 }">{{ currency(value) }}</span>
					</template>
					<template #item.balance_status="{ item }">
						<VChip v-if="getBalanceStatus(item)" :color="getBalanceStatus(item).color" size="small" label>
							<template #prepend>
								<VIcon :icon="getBalanceStatus(item).icon" start />
							</template>
							{{ getBalanceStatus(item).text }}
						</VChip>
					</template>
				</DataTable>
			</VCardText>
		</VCard>
	</VContainer>
</template>

<script setup>
import { DataTable, currency } from "adminui";

const balanceStatuses = {
	credit: { text: "In Credit", color: "success", icon: "mdi-cash-plus" },
	owed: { text: "Money Owed", color: "error", icon: "mdi-alert-circle-outline" },
	settled: { text: "Balance Settled", color: "info", icon: "mdi-check-circle-outline" },
};

/**
 * Derive the balance status from the synced Xero accounts receivable outstanding figure (in pence).
 * A negative outstanding balance means the customer is in credit.
 * Returns null when the account has not been synced with Xero yet.
 */
const getBalanceStatus = (item) => {
	const raw = item?.outstanding;
	if (raw === null || raw === undefined || raw === "") return null;

	const pence = Number(raw);
	if (Number.isNaN(pence)) return null;

	if (pence < 0) return balanceStatuses.credit;
	if (pence > 0) return balanceStatuses.owed;
	return balanceStatuses.settled;
};
</script>
