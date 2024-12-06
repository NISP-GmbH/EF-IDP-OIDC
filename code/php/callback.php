<?php

$debug = false;

if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

require_once '../vendor/autoload.php';
use Jumbojett\OpenIDConnectClient;

session_start();

$oidc = new OpenIDConnectClient(
    '##OIDCPROVIDERURL##',
    '##CLIENTID##',
    '##CLIENTSECRET##'
);

$oidc->setVerifyHost(false);
$oidc->setVerifyPeer(false);
$oidc->setRedirectURL('https://##OIDCAPACHEDOMAIN##/##SSOPATH##/##PHPCALLBACK##');

$oidc->providerConfigParam([
    'issuer' => '##ISSUERENDPOINT##',
    'authorization_endpoint' => '##AUTHENDPOINT##',
    'token_endpoint' => '##TOKENENDPOINT##',
    'userinfo_endpoint' => '##USERINFOENDPOINT##',
    'jwks_uri' => '##JWKSENDPOINT##',
]);

if ($debug) {
    echo "Debug information:<br>";
    echo "Provider URL: " . $oidc->getProviderURL() . "<br>";
    echo "Redirect URL: " . $oidc->getRedirectURL() . "<br>";
    echo "<pre>";
    print_r($oidc);
    echo "</pre>";
}

try {
    $oidc->authenticate();
} catch (Exception $e) {
    echo "Authentication failed: " . $e->getMessage();
}

try {
    $accessToken = $oidc->getAccessToken();
    $idToken = $oidc->getIdToken();
    
    $_SESSION['access_token'] = $accessToken;
    $_SESSION['id_token'] = $idToken;

    if ($debug) {
        error_log("Session data in callback.php: " . print_r($_SESSION, true));
        echo "Session data in callback.php:<pre>" . print_r($_SESSION, true) . "</pre>";
        echo "<p>Click <a href='secure_page.php'>here</a> to continue to secure page.</p>";
        exit;
    }

    header('Location: secure_page.php?token=' . urlencode($accessToken));
    exit;
} catch (Exception $e) {
    echo "Authentication error: " . $e->getMessage();
}
