<?php
declare(strict_types=1);

namespace Firehed\CertStatus\Tools;

use Kubernetes\Api;

class CertScraper
{
    private $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * @return Certificate[]
     */
    public function getAllCertificates(): array
    {
        $version = $this->api->get('/apis/cert-manager.io')['preferredVersion']['version'];
        $base = sprintf('/apis/cert-manager.io/%s', $version);
        $certs = $this->api->get($base.'/certificates')['items'];
        return array_map(function ($cert) {
            return new Certificate($cert);
        }, $certs);
    }

}
