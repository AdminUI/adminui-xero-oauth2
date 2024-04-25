<?php

namespace AdminUI\AdminUIXero\Services;

use Illuminate\Support\Str;
use AdminUI\AdminUI\Models\User;
use AdminUI\AdminUI\Models\Account;
use AdminUI\AdminUIXero\Facades\Xero;
use XeroAPI\XeroPHP\Models\Accounting\Address;

class XeroContactService
{
    public function getContact(Account $account): \XeroAPI\XeroPHP\Models\Accounting\Contact
    {


        // Finding Xero contact by email address
        $user = self::getUser($account);
        // if ($user) {
        //     $contact = self::getContactByEmail($user->email);
        // }

        // if ($contact) {
        //     if (count($contact) == 1) {
        //         // Contact has been found.
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
     * Create a new contact on Xero
     */
    public function createContact(Account $account, User $user): \XeroAPI\XeroPHP\Models\Accounting\Contact
    {
        $billingAddress = $account->addresses->sortByDesc('is_billing')->first();
        $addresses[] = new Address([
            'address_type' => 'POBOX',
            'address_line1' => $billingAddress->address ?? '',
            'address_line2' => $billingAddress->address_2 ?? '',
            'city' => $billingAddress->town ?? '',
            'region' => $billingAddress->county ?? '',
            'postal_code' => $billingAddress->postcode,
            'country' => $billingAddress->country->name ?? 'United Kingdom',
            'attention_to' => $billingAddress->addressee ?? $account->name ?? ''
        ]);

        $contactsToSend = new \XeroAPI\XeroPHP\Models\Accounting\Contacts;
        $contact = new \XeroAPI\XeroPHP\Models\Accounting\Contact([
            'contact_id' => $account->xero_contact_id,
            'name' => $account->name,
            'contact_number' => 'AUI' . $account->id,
            'email_address' => $user->email ?? 'noemail@' . Str::slug($account->name) . 'co.uk',
            'first_name' => $user->first_name ?? $account->name,
            'last_name' => $user->last_name ?? '',
            'tax_number' => $account->tax_number,
            'addresses' => $addresses,
            'phones' => [
                [
                    'phone_type' => 'DEFAULT',
                    'phone_number' => $user->phone ?? '0',
                ],
            ],
            'payment_terms' => [
                'DAYSAFTERBILLDATE' => $account->payment_days ?? 0
            ]
        ]);
        $contactsToSend->setContacts([$contact]);

        $contacts = Xero::updateOrCreateContacts($contactsToSend);
        // self::saveContact($contacts[0], $account);
        return $contacts[0];
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
}
