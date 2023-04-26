<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Version;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class YellowPages
{
    private const ACTION_ADD = 'add';
    private const ACTION_TOUCH = 'touch';
    private const ACTION_REMOVE = 'remove';

    public function __construct(
        private readonly GuzzleClient $httpClient,
        private readonly LoggerInterface $logger
    ) {
    }

    public function add(
        string $endpoint,
        NowPlaying $nowPlaying
    ): PromiseInterface {
        return $this->sendRequest(
            $endpoint,
            self::ACTION_ADD,
            array_filter([
                'sn' => $nowPlaying->station->name,
                'type' => 'music',
                'genre' => $nowPlaying->station->genre,
                'b' => '128',
                'listenurl' => $nowPlaying->station->listen_url,
                'desc' => $nowPlaying->station->description,
                'url' => $nowPlaying->station->url,
            ])
        );
    }

    public function touch(
        string $endpoint,
        string $sessionId,
        NowPlaying $nowPlaying
    ): PromiseInterface {
        return $this->sendRequest(
            $endpoint,
            self::ACTION_TOUCH,
            [
                'sid' => $sessionId,
                'st' => $nowPlaying->now_playing->song->text,
                'listeners' => $nowPlaying->listeners->total,
            ]
        );
    }

    public function remove(
        string $endpoint,
        string $sessionId
    ): PromiseInterface {
        return $this->sendRequest(
            $endpoint,
            self::ACTION_REMOVE,
            [
                'sid' => $sessionId,
            ]
        );
    }

    private function sendRequest(
        string $endpoint,
        string $action,
        array $params
    ): PromiseInterface {
        $requestBody = [
            'action' => $action,
            ...$params,
        ];

        return $this->httpClient->requestAsync(
            'POST',
            $endpoint,
            [
                RequestOptions::HEADERS => [
                    'User-Agent' => 'Icecast (AzuraCast ' . Version::FALLBACK_VERSION . ' )',
                    'Accept' => '*/*',
                ],
                RequestOptions::FORM_PARAMS => $requestBody,
            ]
        )->then(
            function (ResponseInterface $response) use ($action) {
                $responseCode = $response->getHeaderLine('YPResponse');
                if (empty($responseCode)) {
                    throw new \RuntimeException(
                        sprintf(
                            'Remote server indicated failure: %s',
                            $response->getHeaderLine('YPMessage')
                        )
                    );
                }

                if ($response->getStatusCode() !== 200) {
                    throw new \RuntimeException(
                        sprintf(
                            'Request returned status code %d',
                            $response->getStatusCode()
                        )
                    );
                }

                if (self::ACTION_ADD === $action) {
                    $sessionId = $response->getHeaderLine('SID');
                    return (!empty($sessionId))
                        ? $sessionId
                        : false;
                }

                return true;
            }
        );
    }
}
