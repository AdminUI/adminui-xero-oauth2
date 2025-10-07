<?php

namespace AdminUI\AdminUIXero\Handlers;

use AdminUI\AdminUI\Contracts\OrderIntegrationContract;
use AdminUI\AdminUI\Models\OrderIntegration;
use AdminUI\AdminUIXero\Facades\Xero;
use Illuminate\Support\Facades\Cache;

class OrderIntegrationHandler implements OrderIntegrationContract
{
    public function getLink(OrderIntegration $integration): string
    {
        $organisation = Cache::remember('xero_organisation', 5, function () {
            return Xero::getOrganisation() ?? null;
        });

        if ($integration->model_type === "payment") {
            $shortcode = $organisation['short_code'];
            return "https://go.xero.com/organisationlogin/default.aspx?shortcode=$shortcode&redirecturl=/Bank/ViewTransaction.aspx?bankTransactionID=" . $integration->process_id;
        }

        return "";
    }

    public function getColour(OrderIntegration $integration): string
    {
        return "#2baaed";
    }
}
