<?php

namespace AdminUI\AdminUIXero\Handlers;

use AdminUI\AdminUI\Contracts\OrderIntegrationContract;
use AdminUI\AdminUI\Models\OrderIntegration;
use AdminUI\AdminUIXero\Facades\Xero;

class OrderIntegrationHandler implements OrderIntegrationContract
{
    public function getLink(OrderIntegration $integration): string
    {
        if ($integration->model_type === 'payment') {
            // getOrganisation() is already cached, and returns null if Xero can't be reached
            $shortcode = Xero::getOrganisation()?->getShortCode();
            if (empty($shortcode) || empty($integration->process_id)) {
                return '';
            }

            return "https://go.xero.com/organisationlogin/default.aspx?shortcode=$shortcode&redirecturl=/Bank/ViewTransaction.aspx?bankTransactionID=".$integration->process_id;
        }

        return '';
    }

    public function getColour(OrderIntegration $integration): string
    {
        return '#2baaed';
    }
}
