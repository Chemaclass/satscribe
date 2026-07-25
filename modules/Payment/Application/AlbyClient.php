<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use JsonException;
use Modules\Payment\Domain\AlbyClientInterface;
use Modules\Shared\Domain\Data\Payment\InvoiceData;
use RuntimeException;

use function is_array;
use function is_string;

/**
 * @see https://guides.getalby.com/developer-guide/alby-wallet-api
 */
final class AlbyClient implements AlbyClientInterface
{
    private const URL = 'https://api.getalby.com';

    private ?GuzzleClient $client = null;

    public function __construct(
        private readonly string $accessToken,
    ) {
    }

    public function isConnectionValid(): bool
    {
        return $this->accessToken !== '' && $this->accessToken !== '0';
    }

    public function createInvoice(InvoiceData $invoice): array
    {
        $params = [
            'amount' => $invoice->amount,
            'memo' => $invoice->memo,
            'expiry' => $invoice->expiry,
        ];

        if ($invoice->descriptionHash !== null) {
            $params['description_hash'] = $invoice->descriptionHash;
        }

        if ($invoice->description !== null) {
            $params['description'] = $invoice->description;
        }

        $data = $this->request('POST', '/invoices', $params);

        if (!isset($data['payment_hash']) || !is_string($data['payment_hash'])) {
            throw new RuntimeException('Alby invoice response is missing a payment_hash.');
        }

        $data['id'] = $data['payment_hash'];
        $data['r_hash'] = $data['payment_hash'];

        return $data;
    }

    /**
     * @param  string  $hash  Payment hash of the invoice
     */
    public function isInvoicePaid(string $hash): bool
    {
        return (bool) ($this->getInvoice($hash)['settled'] ?? false);
    }

    /**
     * @param  string  $hash  Payment hash of the invoice
     */
    public function getInvoice(string $hash): array
    {
        return $this->request('GET', "/invoices/{$hash}");
    }

    /**
     * @param  array<string, scalar>|null  $body
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => '*',
            'Authorization' => "Bearer {$this->accessToken}",
            'User-Agent' => 'alby-php',
        ];

        $requestBody = $body === null ? null : json_encode($body, JSON_THROW_ON_ERROR);
        $request = new GuzzleRequest($method, $path, $headers, $requestBody);

        try {
            $response = $this->client()->send($request);
            $responseBody = $response->getBody()->getContents();
        } catch (ClientException $e) {
            $error = json_decode($e->getResponse()->getBody()->getContents(), true);
            $message = is_array($error) ? ($error['error'] ?? null) : null;
            throw new RuntimeException(
                is_string($message) ? $message : 'Unknown Alby API error',
                $e->getCode(),
                $e,
            );
        }

        try {
            $decoded = json_decode($responseBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException("Alby returned an unparseable response for {$method} {$path}.", 0, $e);
        }

        if (!is_array($decoded)) {
            throw new RuntimeException("Alby returned a non-object response for {$method} {$path}.");
        }

        return $decoded;
    }

    private function client(): GuzzleClient
    {
        if ($this->client instanceof GuzzleClient) {
            return $this->client;
        }

        $this->client = new GuzzleClient([
            'base_uri' => self::URL,
            'timeout' => 10,
        ]);

        return $this->client;
    }
}
