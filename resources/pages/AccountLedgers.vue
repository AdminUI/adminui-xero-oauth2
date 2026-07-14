<template>
	<VContainer fluid>
		<VCard>
			<VCardText>
				<DataTable model="ledgers" :route="$route">
					<template #item.credit_limit="{ value }">
						{{ currency(value) }}
					</template>
					<template #item.outstanding="{ value }">
						{{ currency(value * 100) }}
					</template>
					<template #item.overdue="{ value }">
						{{ currency(value * 100) }}
					</template>
				</DataTable>
			</VCardText>
		</VCard>
	</VContainer>
</template>

<script setup>
import { DataTable, currency } from "adminui";
</script>
