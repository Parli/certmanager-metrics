<?php
declare(strict_types=1);

use Firehed\API;
use Kubernetes\Api as KubeAPI;
use Psr\Http\Message\ResponseInterface;

$builder = new DI\ContainerBuilder();
$compile = false;
if ($compile) {
    $builder->enableCompilation('.');
}
$builder->useAnnotations(false);
$builder->useAutowiring(true);

$defs = [
    API\Errors\HandlerInterface::class => function ($c) {
        return new class implements API\Errors\HandlerInterface
        {
            function handle($req, $err): ResponseInterface
            {
                return new Zend\Diactoros\Response\TextResponse((string)$err, 500);
            }
        };
    },

    KubeAPI::class => function ($c) {
        if (getenv('KUBERNETES_SERVICE_HOST')) {
            return new Kubernetes\ServiceAccount();
        } else {
            return new Kubernetes\LocalProxy('http://localhost:8001');
        }
    },
];

$builder->addDefinitions($defs);

return $builder->build();
