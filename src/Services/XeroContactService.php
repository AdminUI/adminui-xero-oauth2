<?php

namespace AdminUI\AdminUIXero\Services;

use Illuminate\Support\Str;
use AdminUI\AdminUI\Models\Account;
use AdminUI\AdminUIXero\Facades\Xero;
use AdminUI\AdminUI\Models\User;

class XeroContactService
{
    public function getContact(Account $account)
    {
        // Retrieve an existing contact if it's already been imported
        $contact = self::getContactById($account->import_id);

        // Ensure the contact account is not archived
        if ($contact && $contact['ContactStatus'] == 'ARCHIVED') {
            $account->import_id = null;
            $account->save();
            unset($contact);
        }

        // if a valid contact is found, return it
        if ($contact) {
            return $contact;
        }

        // Fall back to finding Xero contact by email address
        $user = self::getUser($account);
        if ($user) {
            $contact = self::getContactByEmail($user->email);
        }
        if ($contact) {
            if (count($contact) == 1) {
                self::saveContact($contact[0], $account);
                return $contact[0];
            }
        }

        // did nt have a matching email , try to match account name
        $contact = self::getContactByName(self::clean($account->name));
        if ($contact) {
            if (count($contact) == 1) {
                self::saveContact($contact[0], $account);
                return $contact[0];
            }
        }

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
    public function getContactByEmail(string $email): \XeroAPI\XeroPHP\Models\Accounting\Contacts
    {
        return Xero::getContacts(null, 'EmailAddress="' . self::clean($email) . '"');
    }

    /**
     * Search Xero account for a contact by their name
     *
     * @param string $name The name of the person to search for
     */
    public function getContactByName(string $name): \XeroAPI\XeroPHP\Models\Accounting\Contacts
    {
        return Xero::getContacts(null, 'Name="' . self::clean($name) . '"');
    }

    /**
     * Create a new contact on Xero
     */
    public function createContact(Account $account, User $user): \XeroAPI\XeroPHP\Models\Accounting\Contact
    {
        $addresses = $account->addresses;
        if ($addresses) {
            foreach ($addresses as $address) {
                $add[] = [
                    'AddressType' => $address->is_billing ? 'POBOX' : 'STREET',
                    'AddressLine1' => $address->addressee ?? $account->name,
                    'AddressLine2' => $address->address ?? '',
                    'AddressLine3' => $address->address_2 ?? '',
                    'City' => $address->town ?? '',
                    'Region' => $address->county ?? '',
                    'PostalCode' => $address->postcode,
                    'Country' => $address->country->name ?? 'United Kingdom'
                ];
            }
        }

        $contacts = Xero::updateOrCreateContacts([
            'Name' => $account->name,
            'ContactNumber' => 'AUI' . $account->id,
            'EmailAddress' => $user->email ?? 'noemail@' . Str::slug($account->name) . 'co.uk',
            'FirstName' => $user->first_name ?? $account->name,
            'LastName' => $user->last_name ?? '',
            'TaxNumber' => $account->tax_number,
            'Addresses' => $add ?? [],
            'Phones' => [
                [
                    'PhoneType' => 'DEFAULT',
                    'PhoneNumber' => $user->phone ?? '0',
                ],
            ],
            'PaymentTerms' => [
                'DAYSAFTERBILLDATE' => $account->payment_days ?? 0
            ]
        ]);
        self::saveContact($contacts[0], $account);
        sleep(2);
        return $contacts[0];
    }

    /**
     * Save the Xero contact to the current account as an imported ID
     */
    public function saveContact($contact, $account): void
    {
        $account->import_id = $contact['contact_id'];
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
}
