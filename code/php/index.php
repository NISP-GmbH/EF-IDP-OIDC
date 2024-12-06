<?php

require_once 'vendor/autoload.php';
use Jumbojett\OpenIDConnectClient;

session_start();

$debug = false;

if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

$oidc = new OpenIDConnectClient(
    '##OIDCPROVIDERURL##',
    '##CLIENTID##',
    '##CLIENTSECRET##'
);

$oidc->setVerifyHost(false);
$oidc->setVerifyPeer(false);
$oidc->setRedirectURL('https://##OIDCAPACHEDOMAIN##/##SSOPATH##/##PHPCALLBACK##');
$oidc->addScope(['openid', 'profile', 'email']);

$oidc->providerConfigParam([
    'authorization_endpoint' => '##AUTHENDPOINT##',
    'token_endpoint' => '##TOKENENDPOINT##',
    'userinfo_endpoint' => '##USERINFOENDPOINT##',
]);

if ($debug) {
    echo "<pre>";
    print_r($oidc);
    echo "</pre>";
}

try {
    $oidc->authenticate();
    echo "Authentication successful!";
} catch (Exception $e) {
    echo "Authentication failed: " . $e->getMessage();
}
