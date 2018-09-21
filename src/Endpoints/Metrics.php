<?php
declare(strict_types=1);

namespace Firehed\CertStatus\Endpoints;

use Firehed\API\Interfaces\EndpointInterface;
use Firehed\API\Traits\Authentication;
use Firehed\API\Traits\Input;
use Firehed\API\Traits\Request;
use Firehed\CertStatus\Tools\CertScraper;
use Firehed\Input\Containers\SafeInput;
use Firehed\InputObjects;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\TextResponse;

class Metrics implements EndpointInterface
{
    use Authentication\None;
    use Input\NoOptional;
    use Input\NoRequired;
    use Request\Get;
    use RethrowExceptions;

    public function __construct(CertScraper $scraper)
    {
        $this->scraper = $scraper;
    }

    public function getUri(): string
    {
        return '/metrics';
    }

    public function execute(SafeInput $input): ResponseInterface
    {
        $registry = new CollectorRegistry(new InMemory());
        $gauge = $registry->getOrRegisterGauge(
            'kubernetes',
            'certmanager_certificate_expires_seconds',
            'Time until expiration, in seconds',
            ['domain', 'kube_namespace', 'kube_certificate']
        );

        $now = time();
        foreach ($this->scraper->getAllCertificates() as $cert) {
            $info = $cert->getInfo();
            foreach ($info['domains'] as $domain) {
                $gauge->set($info['expires_at'] - $now, [
                    $domain,
                    $info['kube_namespace'],
                    $info['kube_certificate'],
                ]);
            }
        }

        $renderer = new RenderTextFormat();
        $result = $renderer->render($registry->getMetricFamilySamples());

        return (new TextResponse($result))
            ->withHeader('Content-type', RenderTextFormat::MIME_TYPE);
    }
}
