<?php

use App\Services\PayPalClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

describe('PayPalClient', function () {
    it('sends no JSON body when capturing an order', function () {
        Config::set('services.paypal.client_id', 'client-id');
        Config::set('services.paypal.secret', 'secret');
        Config::set('services.paypal.mode', 'live');

        Http::fake([
            'https://api-m.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'test-token',
            ], 200),
            'https://api-m.paypal.com/v2/checkout/orders/ORDER-123/capture' => Http::response([
                'status' => 'COMPLETED',
            ], 200),
        ]);

        app(PayPalClient::class)->captureOrder('ORDER-123');

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api-m.paypal.com/v2/checkout/orders/ORDER-123/capture'
                && trim($request->body()) === '';
        });
    });
});
