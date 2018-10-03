# Certmanager Metrics

This is a simple tool to export additional metrics from [cert-manager](https://github.com/jetstack/cert-manager).

[Docker Hub](https://hub.docker.com/r/parli/certmanager-metrics/)

**Important**: this is a _very_ early release.
While it's probably stable, you should treat it as prototype-quality.
We will make reasonable efforts to avoid breaking changes (including renaming or otherwise altering metrics), but can't promise anything at this time.

_Additionally_, this re-uses the service account and role bindings from cert-manager.
This is (in the current implementation) necessary to read the certificates to parse their expiration dates.
However, that does mean that **by default it has pretty deep access to Kubernetes resources, including secrets** (which is where the certificates are stored).
A future version should reduce the access requirements, but like anything else you should be weary about deploying third-party code that is being granted access to Kubernetes resources.

## Quick Start

This assumes you already have cert-manager up and running, inside the recommended `cert-manager` namespace with its RBAC settings.
Further, this deployment uses Datadog's autodiscovery to have data scraped with Prometheus.
Other Prometheus auto-discovery should be easy to configure, and we welcome contributions to improve this.

Apply the following deployment manifest:

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: certmanager-metrics
  namespace: cert-manager
  labels:
    app: certmanager-metrics
spec:
  replicas: 1
  selector:
    matchLabels:
      app: certmanager-metrics
  template:
    metadata:
      labels:
        app: certmanager-metrics
      annotations:
        ad.datadoghq.com/certmanager-metrics.check_names: '["prometheus"]'
        ad.datadoghq.com/certmanager-metrics.init_configs: '[{}]'
        ad.datadoghq.com/certmanager-metrics.instances: >
          [
            {
              "prometheus_url": "http://%%host%%:10254/metrics",
              "namespace": "kubernetes",
              "metrics": [
                {"kubernetes_certmanager_certificate_expires_seconds": "certmanager.certificate.expires.seconds"}
              ]
            }
          ]
    spec:
      serviceAccountName: cert-manager
      containers:
        - name: certmanager-metrics
          image: parli/certmanager-metrics:latest
          imagePullPolicy: Always
          env:
            - name: ENVIRONMENT
              value: production
            - name: PORT
              value: '10254'
          ports:
            - containerPort: 10254
              name: prometheus
          readinessProbe:
            initialDelaySeconds: 5
            periodSeconds: 5
            timeoutSeconds: 1
            successThreshold: 1
            failureThreshold: 2
            httpGet:
              path: /healthz
              port: prometheus
```

That's it!

## Metrics

| Metric Name | Metric Type | Description | Labels/Tags |
| --- | --- | --- | --- |
| kubernetes\_certmanager\_certificate\_expiration\_seconds | gauge | Time until certificate expiration, in seconds | domain, kube\_namespace, kube\_certificate |


## More info
- [Datadog](docs/datadog.md)
