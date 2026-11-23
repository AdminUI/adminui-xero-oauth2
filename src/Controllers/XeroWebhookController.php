<?php

namespace AdminUI\AdminUIXero\Controllers;

use AdminUI\AdminUIXero\Jobs\SyncXeroAccountBalances;
use Webfox\Xero\Webhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class XeroWebhookController extends Controller
{

    public function __invoke(Request $request, Webhook $webhook)
    {
        // The following lines are required for Xero's 'itent to receive' validation
        if (!$webhook->validate($request->header('x-xero-signature'))) {
            // We can't use abort here, since Xero expects no response body
            return response('', Response::HTTP_UNAUTHORIZED);
        }

        $balanceContactIds = [];
        $balanceInvoiceIds = [];

        // A single webhook trigger can contain multiple events, so we must loop over them
        foreach ($webhook->getEvents() as $event) {
            // Any invoice or contact change may affect an account's balance. Collect the IDs here
            // and refresh balances on the queue, since Xero expects a quick response.
            if ($event->getEventCategory() === 'INVOICE') {
                $balanceInvoiceIds[] = $event->getResourceId();
                continue;
            }
            if ($event->getEventCategory() === 'CONTACT') {
                $balanceContactIds[] = $event->getResourceId();
            }
        }

        if (!empty($balanceContactIds) || !empty($balanceInvoiceIds)) {
            SyncXeroAccountBalances::dispatch(
                array_values(array_unique($balanceContactIds)),
                array_values(array_unique($balanceInvoiceIds)),
            );
        }

        return response('', Response::HTTP_OK);
    }
}
