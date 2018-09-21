<?php
declare(strict_types=1);

use Firehed\API\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;

require __DIR__.'/bootstrap.php';

$config = require 'config.php';

$dispatcher = (new Dispatcher())
    ->setContainer($config)
    ->setEndpointList('__endpoint_list__.json')
    ->setParserList('__parser_list__.json')
    ;

$host = getenv('HOST') ?: '0.0.0.0';
$port = getenv('PORT') ?: 8080;
$loop = React\EventLoop\Factory::create();

$server = new React\Http\Server(function (ServerRequestInterface $request) use ($dispatcher) {
    $dispatcher->setRequest($request);
    return $dispatcher->dispatch();
});

$socket = new React\Socket\Server("$host:$port", $loop);
$server->listen($socket);
echo "Listening on $host:$port\n";

$loop->run();
