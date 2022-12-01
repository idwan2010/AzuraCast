<?php

declare(strict_types=1);

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
ini_set('display_errors', '1');

require dirname(__DIR__) . '/vendor/autoload.php';

$app = App\AppFactory::createApp(
    [
        App\Environment::BASE_DIR => dirname(__DIR__),
    ]
);

$worker = Spiral\RoadRunner\Worker::create();
$psrFactory = new GuzzleHttp\Psr7\HttpFactory();
$psr7 = new Spiral\RoadRunner\Http\PSR7Worker($worker, $psrFactory, $psrFactory, $psrFactory);

while (true) {
    try {
        $request = $psr7->waitRequest();

        if (!($request instanceof Psr\Http\Message\ServerRequestInterface)) {
            // Termination request received
            break;
        }
    } catch (\Throwable) {
        // Bad Request
        $psr7->respond($psrFactory->createResponse(400));
        continue;
    }

    try {
        $psr7->respond($app->handle($request));
    } catch (\Throwable) {
        $psr7->respond($psrFactory->createResponse(500, 'Something Went Wrong!'));
    }

    gc_collect_cycles();
}
