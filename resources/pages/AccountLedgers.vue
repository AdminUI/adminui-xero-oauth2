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
					<template #item.actions.prepend="{ item }">
						<VBtn
							icon="mdi-note-multiple"
							:color="item.notes ? 'primary' : undefined"
							:title="item.notes ? 'Edit notes' : 'Add notes'"
							@click="openNotes(item)"
						/>
					</template>
				</DataTable>
			</VCardText>
		</VCard>

		<VDialog v-model="notesDialog.open" max-width="600" :persistent="notesDialog.saving">
			<VCard :title="`Notes: ${notesDialog.account?.name ?? ''}`" subtitle="These notes are shown on the Point-Of-Sale">
				<VCardText>
					<VTextarea
						v-model="notesDialog.notes"
						label="Notes"
						rows="6"
						auto-grow
						autofocus
						:error-messages="notesDialog.error"
						:disabled="notesDialog.saving"
					/>
				</VCardText>
				<VCardActions>
					<VBtn :disabled="notesDialog.saving" @click="notesDialog.open = false">Cancel</VBtn>
					<VSpacer />
					<VBtn color="primary" variant="flat" :loading="notesDialog.saving" @click="saveNotes">Save</VBtn>
				</VCardActions>
			</VCard>
		</VDialog>
	</VContainer>
</template>

<script setup>
import { DataTable, currency, reactive, axios, router, useRoute, useSnackbar } from "adminui";

const route = useRoute();
const snackbar = useSnackbar();

const notesDialog = reactive({
	open: false,
	saving: false,
	account: null,
	notes: "",
	error: null,
});

const openNotes = (item) => {
	notesDialog.account = item;
	notesDialog.notes = item.notes ?? "";
	notesDialog.error = null;
	notesDialog.open = true;
};

const saveNotes = async () => {
	notesDialog.saving = true;
	notesDialog.error = null;
	try {
		await axios.patch(route("admin.account.update"), {
			id: notesDialog.account.id,
			notes: notesDialog.notes,
		});
		notesDialog.open = false;
		snackbar.add({ text: "Notes saved", type: "success" });
		router.reload({ only: ["ledgers"], preserveScroll: true, preserveState: true });
	} catch (err) {
		notesDialog.error = err.response?.data?.errors?.notes ?? err.response?.data?.message ?? "Failed to save notes";
	} finally {
		notesDialog.saving = false;
	}
};

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
