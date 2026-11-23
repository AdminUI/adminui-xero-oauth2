<?php

namespace AdminUI\AdminUIXero\Services;

use AdminUI\AdminUI\Helpers\Money;
use AdminUI\AdminUI\Models\Account;
use AdminUI\AdminUI\Models\User;
use AdminUI\AdminUIXero\Exceptions\SyncFailedException;
use AdminUI\AdminUIXero\Facades\Xero;
use Illuminate\Support\Str;
use XeroAPI\XeroPHP\Models\Accounting\Address;
use XeroAPI\XeroPHP\Models\Accounting\PaymentTerm;
use XeroAPI\XeroPHP\Models\Accounting\Phone;

class XeroContactService
{
    /**
     * Create or update the Xero contact for an account
     *
     * @throws SyncFailedException When Xero rejects the contact
     */
    public function getContact(Account $account): \XeroAPI\XeroPHP\Models\Accounting\Contact
    {
        $user = $this->getUser($account);
        logger('Xero contact user: ' . ($user?->id ?? 'none found'));

        $contact = $this->createContact($account, $user);
        if (empty($contact)) {
            throw new SyncFailedException(
                "Unable to create or update the Xero contact for account {$account->id}: " . (Xero::getLastError() ?? 'unknown error')
            );
        }

        return $contact;
    }

    /**
     * Retrieve the most senior user registered to an account
     * @param Account $account The account to search
     */
    public function getUser(Account $account): ?User
    {
        $user = $account->owners()->first();
        if (!empty($user)) {
            return $user;
        }
        return $account->users()->first();
    }

    /**
     * Get a Xero contact by their ID
     *
     * @param string $id The Xero ID of the contact
     */
    public function getContactById($id): \XeroAPI\XeroPHP\Models\Accounting\Contact|bool
    {
        if (in_array($id, [0, 1, 2, 3, null])) {
            return false;
        }
        try {
            return Xero::getContact($id)?->getContacts()[0] ?? false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Search Xero account for a contact by their email address
     *
     * @param string $email The email address of the person to search for
     */
    public function getContactByEmail(string $email): \XeroAPI\XeroPHP\Models\Accounting\Contacts|null
    {
        return Xero::getContacts(null, 'EmailAddress="' . Xero::escape(self::clean($email)) . '"') ?? null;
    }

    /**
     * Search Xero account for a contact by their name
     *
     * @param string $name The name of the person to search for
     */
    public function getContactByName(string $name): \XeroAPI\XeroPHP\Models\Accounting\Contacts|null
    {
        return Xero::getContacts(null, 'Name="' . Xero::escape(self::clean($name)) . '"') ?? null;
    }

    /**
     * Search Xero account for a contact by their name
     */
    public function getContactByAccount(string $accountId): \XeroAPI\XeroPHP\Models\Accounting\Contacts|null
    {
        return Xero::getContacts(null, 'AccountNumber="' . Xero::escape(self::clean($accountId)) . '"') ?? null;
    }

    /**
     * Create a new contact on Xero
     */
    public function createContact(Account $account, ?User $user = null): \XeroAPI\XeroPHP\Models\Accounting\Contact|null
    {
        $billingAddress = $account->addresses->sortByDesc('is_billing')->first();
        $addresses = [];
        $addresses[] = new Address([
            'address_type' => Address::ADDRESS_TYPE_POBOX,
            'address_line1' => $billingAddress->address ?? '',
            'address_line2' => $billingAddress->address_2 ?? '',
            'city' => $billingAddress->town ?? '',
            'region' => $billingAddress->county ?? '',
            'postal_code' => $billingAddress->postcode,
            'country' => $billingAddress->country->name ?? 'United Kingdom',
            'attention_to' => $billingAddress->addressee ?? $account->name ?? ''
        ]);

        $phones = [];
        $phones[] = new Phone([
            'phone_type' => 'DEFAULT',
            'phone_number' => $user->phone ?? '0',
        ]);

        // Create PaymentTerm object properly
        $paymentTerms = new PaymentTerm([
            'bills' => [
                'day' => $account->payment_days ?? 0,
                'type' => 'DAYSAFTERBILLDATE'
            ]
        ]);

        // $contactsToSend = new \XeroAPI\XeroPHP\Models\Accounting\Contacts;
        $contactData = [
            'name' => $account->name,
            'contact_number' => 'AUI' . $account->id,
            'account_number' => 'AUI' . $account->id,
            'email_address' => $user->email ?? 'noemail@' . Str::slug($account->name) . '.co.uk',
            'first_name' => $user->first_name ?? $account->name,
            'last_name' => $user->last_name ?? '',
            'tax_number' => $account->tax_number,
            'addresses' => $addresses,
            'phones' => $phones,
            'payment_terms' => $paymentTerms,
        ];

        // Only add contact_id if updating an existing contact
        if (!empty($account->xero_contact_id) && $account->xero_contact_id !== '0') {
            $contactData['contact_id'] = $account->xero_contact_id;
        }

        $contact = new \XeroAPI\XeroPHP\Models\Accounting\Contact($contactData);
        logger('contact: ' . $contact);

        $contactsToSend = new \XeroAPI\XeroPHP\Models\Accounting\Contacts();
        $contactsToSend->setContacts([$contact]);

        try {
            $result = Xero::updateOrCreateContacts($contactsToSend);
            $savedContact = $result?->getContacts()[0] ?? null;
            if (empty($savedContact)) {
                logger('Xero contact creation failed: ' . (Xero::getLastError() ?? 'no response'));
                return null;
            }

            // Save the Xero contact ID back to your account
            if (!empty($savedContact->getContactId())) {
                $account->xero_contact_id = $savedContact->getContactId();
                $account->save();
            }
            return $savedContact;
        } catch (\Throwable $e) {
            logger('Xero contact creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Save the Xero contact to the current account as an imported ID
     */
    public function saveContact($contact, $account): void
    {
        $account->xero_contact_id = $contact['contact_id'];
        $account->save();
    }

    /**
     * Trim and lowercase a given string
     *
     * @param string $string The string to clean
     */
    public function clean(string $string): string
    {
        return str($string)->trim()->lower();
    }

    /**
     * Used by the Ledger, retrieve an account's credit information from its synced Xero balances.
     * Accounts that have never been synced are synced on demand.
     */
    public function getCreditLimit(Account $account): ?array
    {
        if (empty($account->xero_balances_synced_at)) {
            // Older accounts may only be linked to Xero by account number, so link them by ID first
            if (empty($account->xero_contact_id) || $account->xero_contact_id === '0') {
                $contact = $this->getContactByAccount('AUI' . $account->id)?->getContacts()[0] ?? null;
                if (empty($contact)) {
                    return null;
                }
                $this->saveContact($contact, $account);
            }

            $this->syncBalances([$account->xero_contact_id]);
            $account->refresh();

            if (empty($account->xero_balances_synced_at)) {
                return null;
            }
        }

        $outstanding = (int) $account->xero_outstanding;

        return [
            'credited' => 0,
            'debited' => 0,
            'balance' => $outstanding,
            'overdue' => (int) $account->xero_overdue,
            'available' => $account->credit_limit - $outstanding,
        ];
    }

    /**
     * Fetch accounts receivable balances from Xero and store them against their accounts.
     *
     * @param string[]|null $contactIds Limit the sync to these Xero contact IDs. Null syncs every linked account.
     * @return int The number of accounts updated
     */
    public function syncBalances(?array $contactIds = null): int
    {
        $query = Account::query()
            ->whereNotNull('xero_contact_id')
            ->where('xero_contact_id', '!=', '');

        if ($contactIds !== null) {
            $contactIds = array_values(array_unique(array_filter($contactIds)));
            if (empty($contactIds)) {
                return 0;
            }
            $query->whereIn('xero_contact_id', $contactIds);
        }

        $updated = 0;

        // Xero accepts a list of IDs per request; keep batches small enough for the URL length
        $query->select(['id', 'xero_contact_id'])->chunkById(50, function ($accounts) use (&$updated) {
            $response = Xero::getContacts(ids: $accounts->pluck('xero_contact_id')->unique()->values()->all());
            if (empty($response)) {
                return;
            }

            $contacts = collect($response->getContacts())->keyBy(fn($contact) => $contact->getContactId());
            $now = now();

            foreach ($accounts as $account) {
                $contact = $contacts->get($account->xero_contact_id);
                if (empty($contact)) {
                    continue;
                }

                $receivable = $contact->getBalances()?->getAccountsReceivable();

                Account::whereKey($account->id)->update([
                    'xero_outstanding' => Money::convertToSubunit($receivable?->getOutstanding() ?? 0),
                    'xero_overdue' => Money::convertToSubunit($receivable?->getOverdue() ?? 0),
                    'xero_balances_synced_at' => $now,
                ]);

                $updated++;
            }
        });

        return $updated;
    }
}
