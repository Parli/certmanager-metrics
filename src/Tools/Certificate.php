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
    /** @var Api */
    private $api;

    /** @var array K8s Cert object */
    private $k8sCert;

    /** @var string actual cert, in pem format */
    private $cert;

    /**
     * @param Api $api Kubernetes API connection
     * @param array $cert Kubernetes certificate object
     */
    public function __construct(Api $api, array $cert)
    {
        $this->api = $api;
        $this->k8sCert = $cert;
    }

    public function getInfo(): array
    {
        $certInfo = openssl_x509_parse($this->getCertificate());

        return [
            'domains' => $this->k8sCert['spec']['dnsNames'],
            'expires_at' => $certInfo['validTo_time_t'],
            'kube_namespace' => $this->k8sCert['metadata']['namespace'],
            'kube_certificate' => $this->k8sCert['metadata']['name'],
        ];
    }

    /**
     * Returns the certificate, in PEM format. May fetch from an external
     * source.
     */
    private function getCertificate()
    {
        if (!$this->cert) {
            // This fetches from the secret. This is sub-optimal from
            // a security standpoint since it requires additional permission
            // grants, but should be both faster and less dependent on the
            // external network.
            // At some point this should be revisited in order to reduce the
            // required permissions for the deployment role
            $secretUrl = sprintf(
                '/api/v1/namespaces/%s/secrets/%s',
                $this->k8sCert['metadata']['namespace'],
                $this->k8sCert['metadata']['name']
            );
            $secret = $this->api->get($secretUrl);
            $this->cert = base64_decode($secret['data']['tls.crt']);
        }
        return $this->cert;
    }
}
