<?php

namespace AdminUI\AdminUIXero\Services;

use Illuminate\Support\Str;
use AdminUI\AdminUI\Models\User;
use AdminUI\AdminUI\Helpers\Money;
use AdminUI\AdminUI\Models\Account;
use AdminUI\AdminUIXero\Facades\Xero;
use XeroAPI\XeroPHP\Models\Accounting\Phone;
use XeroAPI\XeroPHP\Models\Accounting\Address;
use XeroAPI\XeroPHP\Models\Accounting\PaymentTerm;

class XeroContactService
{
    public function getContact(Account $account): \XeroAPI\XeroPHP\Models\Accounting\Contact
    {


        // Finding Xero contact by email address
        $user = self::getUser($account);
        logger('User:' . $user->id ?? 'none found');

        // if ($user) {
        //     $contact = self::getContactByEmail($user->email);
        // }

        // if ($contact) {
        //     if (count($contact) == 1) {
        //         // Contachas been found.
        //         // Update the contact

        //         return $contact[0];
        //     }
        // }

        // // did nt have a matching email , try to match account name
        // $contact = self::getContactByName(self::clean($account->name));
        // if ($contact) {
        //     if (count($contact) == 1) {
        //         // Contact has been found.
        //         // Update the contact

        //         return $contact[0];
        //     }
        // }

        // definitely does not exist, create a new contact
        return self::createContact($account, $user);
    }

    /**
     * Retrieve the most senior user registered to an account
     * @param Account $account The account to search
     */
    public function getUser(Account $account): User|bool
    {
        $user = $account->owners()->first();
        if (!empty($user)) {
            return $user;
        }
        $user = $account->users()->first();
        if (!empty($user)) {
            return $user;
        }
        return false;
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
            $contacts = Xero::getContact($id);
            return $contacts[0];
        } catch (\Exception $e) {
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
        return Xero::getContacts(null, 'EmailAddress="' . self::clean($email) . '"') ?? null;
    }

    /**
     * Search Xero account for a contact by their name
     *
     * @param string $name The name of the person to search for
     */
    public function getContactByName(string $name): \XeroAPI\XeroPHP\Models\Accounting\Contacts|null
    {
        return Xero::getContacts(null, 'Name="' . self::clean($name) . '"') ?? null;
    }

    /**
     * Search Xero account for a contact by their name
     *
     * @param string $name The name of the person to search for
     */
    public function getContactByAccount(string $accountId): \XeroAPI\XeroPHP\Models\Accounting\Contacts|null
    {
        return Xero::getContacts(null, 'AccountNumber="' . self::clean($accountId) . '"') ?? null;
    }

    /**
     * Create a new contact on Xero
     */
    public function createContact(Account $account, User $user): \XeroAPI\XeroPHP\Models\Accounting\Contact
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
            'email_address' => $user->email ?? 'noemail@' . Str::slug($account->name) . 'co.uk',
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

            // Save the Xero contact ID back to your account
            if (!empty($result->getContacts()[0]->getContactId())) {
                $account->xero_contact_id = $result->getContacts()[0]->getContactId();
                $account->save();
            }
            return $result->getContacts()[0];
        } catch (\Exception $e) {
            logger('Xero contact creation failed: ' . $e->getMessage());
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
     * Used by the Ledger, retrieve an account's credit limit from Xero.
     */
    public function getCreditLimit(Account $account): ?array
    {
        $contact = $this->getContactByAccount('AUI' . $account->id);


        $outstanding = 0;
        $overdue = 0;
        if (empty($contact)) {
            return null;
        }
        if (isset($contact[0]['balances']['accounts_receivable'])) {
            $outstanding = Money::convertToSubunit($contact[0]['balances']['accounts_receivable']['outstanding'] ?? 0);
            $overdue = Money::convertToSubunit($contact[0]['balances']['accounts_receivable']['overdue']);
        }
        $available = $account->credit_limit - $outstanding;
        return [
            'credited' => 0,
            'debited' => 0,
            'balance' => $outstanding,
            'overdue' => $overdue,
            'available' => $available
        ];
    }
}
