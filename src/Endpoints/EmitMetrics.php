<?php
declare(strict_types=1);

namespace Firehed\CertStatus\Endpoints;

use Firehed\API\Interfaces\EndpointInterface;
use Firehed\API\Traits\Authentication;
use Firehed\API\Traits\Input;
use Firehed\API\Traits\Request;
use Firehed\Input\Containers\SafeInput;
use Firehed\InputObjects;
use Kubernetes\Api;
use Psr\Http\Message\ResponseInterface;

class EmitMetrics implements EndpointInterface
{
    use Authentication\None;
    use Input\NoOptional;
    use Input\NoRequired;
    use Request\Get;
    use RethrowExceptions;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function getUri(): string
    {
        return '/emitMetrics';
    }

    public function execute(SafeInput $input): ResponseInterface
    {
        $this->certStatus();
        return new \Zend\Diactoros\Response\TextResponse('OK');
    }

    private function certStatus() {
        $version = $this->api->get('/apis/certmanager.k8s.io')['preferredVersion']['version'];
        $base = sprintf('/apis/certmanager.k8s.io/%s', $version);
        $certs = $this->api->get($base.'/certificates')['items'];

        foreach ($certs as $cert) {
            $this->showCert($cert);
        }
    }


    private function showCert(array $cert) {
        $names = $cert['spec']['dnsNames'];
        // print_r($cert['spec']['dnsNames']);
        $secretUrl = sprintf('/api/v1/namespaces/%s/secrets/%s', $cert['metadata']['namespace'], $cert['metadata']['name']);
        $secret = $this->api->get($secretUrl);
        $pem = base64_decode($secret['data']['tls.crt']);
        $r = openssl_x509_parse($pem);
        // print_r($cert);
        // print_r($r);
        $expireTime = $r['validTo_time_t'];

        $remainingSeconds = $expireTime - time();
        $days = round($remainingSeconds / 86400, 1);

        foreach ($names as $name) {
            $tags = [
                ['server_name', $name],
                ['kube_namespace', $cert['metadata']['namespace']],
                ['kube_certificate', $cert['metadata']['name']],
            ];
            $tagStr = implode(',', array_map(function ($el) {
                return $el[0].':'.$el[1];
            }, $tags));

            echo "kubernetes.ingress.certificate.seconds_remaining:$remainingSeconds|g|#$tagStr\n";
        }
        // echo "Expires in $remainingSeconds sec ($days days)\n";

    }

}
