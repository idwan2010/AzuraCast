<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Api\NowPlaying\NowPlaying;
use App\Entity\Station;
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
        Station $station
    ): PromiseInterface {
        return $this->sendRequest(
            $endpoint,
            self::ACTION_ADD,
            [
                'sn' => $station->getName(),
                'type' => 'music',
                'genre' => $station->getGenre(),
                'b' => '128',
                'listenurl' => '', // TODO
                'desc' => $station->getDescription(),
                'url' => $station->getUrl(),
            ]
        );
    }

    public function touch(
        string $endpoint,
        string $sessionId,
        Station $station,
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
                    'User-Agent' => 'AzuraCast ' . Version::FALLBACK_VERSION,
                    'Accept' => '*/*',
                ],
                RequestOptions::BODY => $requestBody,
            ]
        )->then(
            function (ResponseInterface $response) use ($requestBody) {
                if ($response->getStatusCode() !== 200) {
                    $this->logger->error(
                        sprintf('Request returned status code %d.', $response->getStatusCode()),
                        [
                            'body' => $requestBody,
                        ]
                    );
                    return false;
                }

                $responseCode = $response->getHeaderLine('YPResponse');
                if (empty($responseCode)) {
                    $this->logger->error(
                        sprintf(
                            'Remote server indicated failure: %s',
                            $response->getHeaderLine('YPMessage')
                        ),
                        [
                            'body' => $requestBody,
                        ]
                    );
                    return false;
                }

                if (self::ACTION_ADD === $requestBody['action']) {
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
