<?php

declare(strict_types=1);

namespace PaperclipBilling;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class PaperclipClient
{
    private Client $http;
    private string $companyId;

    public function __construct(string $apiUrl, string $apiKey, string $companyId)
    {
        $this->companyId = $companyId;
        $this->http = new Client([
            'base_uri' => rtrim($apiUrl, '/'),
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchIssues(?string $status = null): array
    {
        $query = ['status' => $status ?? 'done,in_progress,cancelled'];

        $response = $this->http->get("/api/companies/{$this->companyId}/issues", [
            'query' => $query,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchAgents(): array
    {
        $response = $this->http->get("/api/companies/{$this->companyId}/agents");

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchCostsByProject(): array
    {
        $response = $this->http->get("/api/companies/{$this->companyId}/costs/by-project");

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchCostsByAgent(): array
    {
        $response = $this->http->get("/api/companies/{$this->companyId}/costs/by-agent");

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getCompanyId(): string
    {
        return $this->companyId;
    }
}
