---
title: "TLS, proxies and trust boundaries"
---

TLS verification is part of the supported transport contract. Configure trust explicitly when using a private CA; do not disable verification to make a test pass. Certificate identity, proxy identity and origin identity are separate security boundaries.

## Origin TLS and client certificates

```php
use Asyncara\HTTP\HttpClient;
$http = new HttpClient(
    caFile: '/etc/app/origin-ca.pem',
    clientCertFile: '/etc/app/client-chain.pem',
    clientKeyFile: '/etc/app/client-key.pem',
);
```

Client certificate and key paths must be supplied together and refer to local files. They are read through bounded worker-backed preparation and retained as an identity snapshot by that client. A new client is needed to pick up a changed identity. Replacing two files is not an atomic certificate/key snapshot. Encrypted private keys and PKCS#11 callbacks are not part of this API.

## Explicit proxy configuration

```php
$http = new HttpClient(
    proxy: 'https://proxy.example:8443',
    caFile: '/etc/app/origin-ca.pem',
    proxyCaFile: '/etc/app/proxy-ca.pem',
    proxyCredentials: ['username' => getenv('PROXY_USER'), 'password' => getenv('PROXY_PASSWORD')],
);
$response = $http->get('https://service.example/data')->await();
```

Validate required environment variables before construction. Proxy URLs must not embed userinfo when explicit credentials are used. Origin and proxy CA verification remain independent. Basic credentials on a plaintext HTTP proxy are plaintext on that hop, even when CONNECT later tunnels origin TLS. Choose an HTTPS proxy when the proxy hop needs encryption. Do not inject Proxy-Authorization as an application header.

Explicit HTTP/HTTPS forward proxies and CONNECT are supported in the tested transport paths. SOCKS 5 uses local origin DNS; SOCKS 5h delegates it to the proxy. Use `socks5://proxy.example:1080` or `socks5h://proxy.example:1080`, with explicit username/password where required. Hyper does not automatically consume environment proxy variables. SOCKS 4, BIND/UDP, Digest/NTLM/Negotiate and universal appliance compatibility are not claimed.

## Redirects and SSRF

Do not pass arbitrary user URLs directly to HttpClient. Define allowed schemes, hosts and ports, and decide whether redirects are permitted. DNS validation has rebinding and multiple-address subtleties; simple string filtering is not a complete SSRF defense. If a service must fetch untrusted URLs, enforce network-level egress policy as well.

Following a redirect can change the destination. Standard sensitive headers are removed by the owned Hyper redirect policy across origins, but custom secrets still need application rules. mTLS redirects reject an origin change. HTTP error responses and proxy authentication refusal are not all the same failure mode: inspect status where a response exists, catch transport failures where the tunnel cannot be established, and never print credentials in error messages.
