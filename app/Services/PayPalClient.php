<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalClient
{
    public function baseUrl(): string
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function clientId(): ?string
    {
        return config('services.paypal.client_id');
    }

    public function configured(): bool
    {
        return filled(config('services.paypal.client_id')) && filled(config('services.paypal.secret'));
    }

    public function accessToken(): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('PayPal no está configurado. Define PAYPAL_CLIENT_ID y PAYPAL_SECRET en .env.');
        }

        $request = Http::asForm()
            ->withBasicAuth((string) config('services.paypal.client_id'), (string) config('services.paypal.secret'));

        // En desarrollo local, deshabilitamos verificación SSL
        if (app()->environment('local')) {
            $request = $request->withoutVerifying();
        }

        $response = $request->post($this->baseUrl().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if (! $response->successful()) {
            throw new RuntimeException('PayPal no entregó token OAuth: '.$response->body());
        }

        return (string) data_get($response->json(), 'access_token');
    }

    private function request(bool $asJson = true): PendingRequest
    {
        $request = Http::withToken($this->accessToken())
            ->acceptJson();

        if ($asJson) {
            $request = $request->asJson();
        }

        // En desarrollo local, deshabilitamos verificación SSL para evitar errores de certificado
        if (app()->environment('local')) {
            $request = $request->withoutVerifying();
        }

        return $request;
    }

    public function createOrder(string $reference, float $amount, string $description, string $returnUrl, string $cancelUrl, string $currency = 'MXN'): array
    {
        $response = $this->request()->post($this->baseUrl().'/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $reference,
                'description' => mb_substr($description, 0, 127),
                'amount' => [
                    'currency_code' => $currency,
                    'value' => number_format($amount, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'brand_name' => 'IRIS',
                'landing_page' => 'LOGIN',
                'user_action' => 'PAY_NOW',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('No fue posible crear la orden PayPal: '.$response->body());
        }

        return $response->json();
    }

    public function captureOrder(string $orderId): array
    {
        // PayPal requiere un POST sin body JSON. withBody('', 'application/json') evita
        // que Laravel/Guzzle serialice un array vacío como {} o [], lo que causaba MALFORMED_REQUEST_JSON.
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->withBody('', 'application/json')
            ->when(app()->environment('local'), fn ($r) => $r->withoutVerifying())
            ->post($this->baseUrl().'/v2/checkout/orders/'.$orderId.'/capture');

        if (! $response->successful()) {
            throw new RuntimeException('No fue posible capturar la orden PayPal: '.$response->body());
        }

        return $response->json();
    }

    public function refundCapture(string $captureId, float $amount, string $currency = 'MXN', ?string $note = null): array
    {
        $payload = [
            'amount' => [
                'currency_code' => $currency,
                'value' => number_format($amount, 2, '.', ''),
            ],
        ];

        if ($note) {
            $payload['note_to_payer'] = mb_substr($note, 0, 255);
        }

        $response = $this->request()->post($this->baseUrl().'/v2/payments/captures/'.$captureId.'/refund', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('No fue posible reembolsar el pago PayPal: '.$response->body());
        }

        return $response->json();
    }

    public function approvalUrl(array $order): ?string
    {
        foreach (($order['links'] ?? []) as $link) {
            if (($link['rel'] ?? null) === 'approve') {
                return $link['href'] ?? null;
            }
        }
        return null;
    }
}