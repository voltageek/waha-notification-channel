<?php

namespace Cactus\Notifications\Channels\WAHA;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Str;
use Cactus\Notifications\Channels\WAHA\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;

class Whatsapp
{
    public function __construct(
        protected ?string $apiKey = null,
        protected ?string $apiBaseUri = null,
        protected ?string $session = 'default',
        protected HttpClient $http = new HttpClient,
    ) {
        $this->setApiBaseUri("{$apiBaseUri}/api");
        $this->setSession($session);
    }

    /**
     * Session getter.
     */
    public function getSession(): ?string
    {
        return $this->session;
    }

    /**
     * Session setter.
     *
     * @return $this
     */
    public function setSession(string $session): self
    {
        $this->session = $session;

        return $this;
    }

    /**
     * API Base URI getter.
     */
    public function getApiBaseUri(): string
    {
        return $this->apiBaseUri;
    }

    /**
     * API Base URI setter.
     *
     * @return $this
     */
    public function setApiBaseUri(string $apiBaseUri): self
    {
        $this->apiBaseUri = rtrim($apiBaseUri, '/');

        return $this;
    }

    /**
     * Set HTTP Client.
     *
     * @return $this
     */
    public function setHttpClient(HttpClient $http): self
    {
        $this->http = $http;

        return $this;
    }

    /**
     * Send text message.
     *
     * <code>
     * $params = [
     *   'session'                  => '',
     *   'text'                     => '',
     *   'chatId'                   => '',
     * ];
     * </code>
     *
     * @throws CouldNotSendNotification
     */
    public function sendMessage(array $params): ?ResponseInterface
    {
        return $this->sendRequest('sendText', $params);
    }

    /**
     * Send File as Image or Document.
     *
     *
     * @throws CouldNotSendNotification
     */
    public function sendFile(array $params, string $type): ?ResponseInterface
    {
        $endpoint = match($type){
            'photo' => 'sendImage',
            'document' => 'sendFile'
        };

        return $this->sendRequest($endpoint, $params);
    }

    /**
     * Get HttpClient.
     */
    protected function httpClient(): HttpClient
    {
        return $this->http;
    }

    /**
     * Send an API request and return response.
     *
     *
     * @throws CouldNotSendNotification
     */
    protected function sendRequest(string $endpoint, array $params): ?ResponseInterface
    {
        $apiUri = sprintf('%s/%s', $this->apiBaseUri, $endpoint);
        
        if(!\Arr::has($params, 'session')) {
            \Arr::set($params, 'session', $this->getSession());
        }

        try {
            return $this->httpClient()->post($apiUri, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Api-Key' => 'D27B1D94745D53E417B2FD9C1F522'
                ],
                RequestOptions::JSON => $params,
            ]);
        } catch (ClientException $exception) {
            throw CouldNotSendNotification::wahaRespondedWithAnError($exception);
        } catch (Exception $exception) {
            throw CouldNotSendNotification::couldNotCommunicateWithWaha($exception);
        }
    }
}
