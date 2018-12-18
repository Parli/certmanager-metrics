<?php
declare(strict_types=1);

use function DI\autowire;
use Firehed\API;
use Firehed\CertStatus\Endpoints;
use Firehed\SimpleLogger\Stdout;
use Kubernetes\Api as KubeAPI;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

$builder = new DI\ContainerBuilder();
$env = getenv('ENVIRONMENT');
$compile = ($env && $env !== 'development');
if ($compile) {
    $builder->enableCompilation('.');
}
$builder->useAnnotations(false);
$builder->useAutowiring(true);

$defs = [
    API\Errors\HandlerInterface::class => function ($c) {
        $logger = $c->get(LoggerInterface::class);

        return new class ($logger) implements API\Errors\HandlerInterface
        {
            private $logger;
            public function __construct(LoggerInterface $logger)
            {
                $this->logger = $logger;
            }

            function handle($req, $err): ResponseInterface
            {
                $this->logger->error((string)$err);
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

    LoggerInterface::class => function () {
        return new Stdout();
    },

    Endpoints\Healthz::class => autowire(),
    Endpoints\Metrics::class => autowire(),
];

$builder->addDefinitions($defs);

return $builder->build();
