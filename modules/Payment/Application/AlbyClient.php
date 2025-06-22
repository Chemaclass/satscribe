<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Modules\Payment\Domain\AlbyClientInterface;
use Modules\Shared\Domain\Data\Payment\InvoiceData;
use RuntimeException;

/**
 * @see https://guides.getalby.com/developer-guide/alby-wallet-api
 */
final class AlbyClient implements AlbyClientInterface
{
    private const URL = "https://api.getalby.com";

    private ?GuzzleClient $client = null;

    public function __construct(
        private readonly string $accessToken,
    ) {
    }

    /**
     * Retrieve the authenticated user information from Alby.
     *
     * @return array User details with additional alias and identity_pubkey fields
     */
    public function getInfo(): array
    {
        $data = $this->request("GET", "/user/me") ?? [];
        $data["alias"] = "getalby.com";
        $data["identity_pubkey"] = "";
        return $data;
    }

    /**
     * @param  string  $method  HTTP method (GET, POST, etc.)
     * @param  string  $path  API endpoint path
     * @param  mixed|null  $body  Request payload (optional)
     *
     * @return array|null Decoded JSON response as an associative array
     */
    private function request(string $method, string $path, mixed $body = null): ?array
    {
        $headers = [
            "Accept" => "application/json",
            "Content-Type" => "application/json",
            "Access-Control-Allow-Origin" => "*",
            "Authorization" => "Bearer {$this->accessToken}",
            "User-Agent" => "alby-php",
        ];

        $requestBody = $body ? json_encode($body) : null;
        $request = new GuzzleRequest($method, $path, $headers, $requestBody);

        try {
            $response = $this->client()->send($request);
            $responseBody = $response->getBody()->getContents();
            return json_decode($responseBody, true);
        } catch (ClientException $e) {
            $error = json_decode($e->getResponse()->getBody()->getContents(), true);
            throw new RuntimeException($error["error"] ?? 'Unknown Alby API error', $e->getCode(), $e);
        }
    }

    /**
     * Get or initialize the Guzzle HTTP client.
     */
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

    /**
     * Retrieve the current wallet balance.
     *
     * @return array Balance information
     */
    public function getBalance(): array
    {
        return $this->request("GET", "/balance") ?? [];
    }

    /**
     * Check if the connection to Alby is valid.
     */
    public function isConnectionValid(): bool
    {
        return $this->accessToken !== '' && $this->accessToken !== '0';
    }

    public function createInvoice(InvoiceData $invoice): array
    {
        $params = [
            "amount" => $invoice->amount,
            "memo" => $invoice->memo,
        ];

        if ($invoice->descriptionHash !== null) {
            $params['description_hash'] = $invoice->descriptionHash;
        }

        if ($invoice->description !== null) {
            $params['description'] = $invoice->description;
        }

        if ($invoice->expiry !== null) {
            $params['expiry'] = $invoice->expiry;
        }

        $data = $this->request("POST", "/invoices", $params) ?? [];

        $data["id"] = $data["payment_hash"];
        $data["r_hash"] = $data["payment_hash"];

        return $data;
    }

    /**
     * @param  string  $hash  Payment hash of the invoice
     */
    public function isInvoicePaid(string $hash): bool
    {
        $invoice = $this->getInvoice($hash);
        return $invoice["settled"] ?? false;
    }

    /**
     * @param  string  $hash  Payment hash of the invoice
     *
     * @return array Invoice details
     */
    public function getInvoice(string $hash): array
    {
        return $this->request("GET", "/invoices/{$hash}") ?? [];
    }
}
