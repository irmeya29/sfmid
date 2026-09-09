<?php

namespace Tests\Feature\Clients;

use App\Models\Client;
use Tests\TestCase;

class ClientDocumentIdentityTest extends TestCase
{
    public function test_document_displays_supplied_client_information_and_escapes_it(): void
    {
        $client = new Client([
            'name' => 'Client <test>',
            'address' => 'Secteur 54, parcelle 01',
            'tax_regime' => 'RNI DGE',
            'legal_form' => 'SA',
            'cnss' => '1289807H',
            'share_capital' => '10 000 000 FCFA',
            'postal_address' => '06 BP 9584 OUAGA 06',
            'rccm' => 'BFOUA-1-2026-M-07369',
            'ifu' => '00096954V',
        ]);

        $view = $this->view('pdf._client_identity', compact('client'));
        foreach ($client->getAttributes() as $value) {
            $view->assertSee($value);
        }
        $view->assertDontSee('Client <test>', false);
    }

    public function test_document_omits_empty_client_information(): void
    {
        $client = new Client(['name' => 'Client simple', 'address' => '   ', 'cnss' => null]);
        $this->view('pdf._client_identity', compact('client'))
            ->assertSee('Client simple')
            ->assertDontSee('Adresse')
            ->assertDontSee('CNSS')
            ->assertDontSee('RCCM')
            ->assertDontSee('IFU')
            ->assertDontSee('Capital social')
            ->assertDontSee('Email');
    }
}
