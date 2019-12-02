<?php
declare(strict_types=1);

namespace Firehed\CertStatus\Tools;

use Kubernetes\Api;

/**
 * This is a data model for a Kubernetes certificate (from a CertManager CRD).
 * It will use the data from the cert object to fetch the actual certificate in
 * order to parse it and provide additional information as needed.
 */
class Certificate
{
    /** @var array K8s Cert object */
    private $k8sCert;

    /**
     * @param Api $api Kubernetes API connection
     * @param array $cert Kubernetes certificate object
     */
    public function __construct(array $cert)
    {
        $this->k8sCert = $cert;
    }

    public function getInfo(): array
    {
        return [
            'domains' => $this->k8sCert['spec']['dnsNames'],
            'expires_at' => strtotime($this->k8sCert['status']['notAfter']),
            'kube_namespace' => $this->k8sCert['metadata']['namespace'],
            'kube_certificate' => $this->k8sCert['metadata']['name'],
        ];
    }
}
