<?php
declare(strict_types=1);

namespace Src\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class iAvProducerService
{

    private string $apiUrl;
    private HttpClientInterface $client;
    private LoggerInterface $logger;

    public function __construct(string $apiUrl, HttpClientInterface $client, LoggerInterface $logger)
    {
        $this->apiUrl = $apiUrl;
        $this->client = $client;
        $this->logger = $logger;
    }

    public function send(array $message)
    {
        $this->logger->debug('make request', ['domain' => $message['domain'] ?? null, 'partner_id' => $message['partner_id'] ?? null, 'notification_id' => $message['message']['data']['id']]);
        $response = $this->client->request(
            'POST', $this->apiUrl, ['json' => $message]
        );
        $this->logger->debug('got response', ['body' => $response->getContent(), 'status_code' => $response->getStatusCode()]);
    }

}