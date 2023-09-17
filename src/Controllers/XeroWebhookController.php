<?php

namespace AdminUI\AdminUIXero\Controllers;

use Webfox\Xero\Webhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use XeroApi\XeroPHP\Models\Accounting\Contact;
use XeroApi\XeroPHP\Models\Accounting\Invoice;

class XeroWebhookController extends Controller
{

    public function __invoke(Request $request, Webhook $webhook)
    {
        // The following lines are required for Xero's 'itent to receive' validation
        if (!$webhook->validate($request->header('x-xero-signature'))) {
            // We can't use abort here, since Xero expects no response body
            return response('', Response::HTTP_UNAUTHORIZED);
        }

        // A single webhook trigger can contain multiple events, so we must loop over them
        foreach ($webhook->getEvents() as $event) {
            if ($event->getEventType() === 'CREATE' && $event->getEventCategory() === 'INVOICE') {
                $this->invoiceCreated($request, $event->getResource());
            } elseif ($event->getEventType() === 'CREATE' && $event->getEventCategory() === 'CONTACT') {
                $this->contactCreated($request, $event->getResource());
            } elseif ($event->getEventType() === 'UPDATE' && $event->getEventCategory() === 'INVOICE') {
                $this->invoiceUpdated($request, $event->getResource());
            } elseif ($event->getEventType() === 'UPDATE' && $event->getEventCategory() === 'CONTACT') {
                $this->contactUpdated($request, $event->getResource());
            }
        }

        return response('', Response::HTTP_OK);
    }

    protected function invoiceCreated(Request $request, Invoice $invoice)
    {
        Log::debug('Webhook: Invoice was created');
    }

    protected function contactCreated(Request $request, Contact $contact)
    {
        Log::debug('Webhook: Contact was created');
    }

    protected function invoiceUpdated(Request $request, Invoice $invoice)
    {
        Log::debug('Webhook: Invoice was updated');
    }

    protected function contactUpdated(Request $request, Contact $contact)
    {
        Log::debug('Webhook: Contact was update');
    }
}
