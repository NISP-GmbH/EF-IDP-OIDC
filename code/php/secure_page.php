<?php

$debug = false;

require_once '../vendor/autoload.php';
use Jumbojett\OpenIDConnectClient;

session_start();

function makeRequest($url, $method = 'GET', $data = null, $headers = [])
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    // Ignore SSL certificate validation
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('Curl error: ' . curl_error($ch));
    }

    curl_close($ch);

    return $result;
}

// Validate the token format
function validateTokenFormat($token, $maxLength)
{
    $allowedChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_.-';
    return strlen($token) <= $maxLength && strspn($token, $allowedChars) === strlen($token);
}

// Check token validity (get preferred_username)
function checkTokenAndGetPreferredUsername($token)
{
    $userinfo_url = '##USERINFOENDPOINT##';
    $headers = [
        'Authorization: Bearer ' . $token
    ];

    $response = makeRequest($userinfo_url, 'GET', null, $headers);
    $user_data = json_decode($response, true);

    if (isset($user_data['preferred_username'])) {
        return $user_data['preferred_username'];
    }
    return null;
}

function encrypt($data, $key, $nonce)
{
    // Convert the key and nonce from hex to binary
    $key = hex2bin($key);
    $nonce = hex2bin($nonce);

    // Ensure the key and nonce are the correct length for AES-128-CTR
    if (strlen($key) !== 16) {
        throw new Exception("Key must be 16 bytes long.");
    }
    if (strlen($nonce) !== 16) {
        throw new Exception("Nonce must be 16 bytes long.");
    }

    // Set the cipher method
    $cipher = 'aes-128-ctr';

    // Encrypt the data
    $encrypted = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $nonce);

    // Encode the result in base64 to make it URL safe
    return base64_encode($encrypted);
}

// Initialize cookies array
$cookies = [];

// Function to extract cookies from response headers
function extractCookies($responseHeaders)
{
    $cookies = [];
    foreach ($responseHeaders as $header) {
        if (preg_match('/^Set-Cookie:\s*([^;]*)/mi', $header, $matches)) {
            parse_str($matches[1], $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
    }
    return $cookies;
}

// Function to format cookies as a string
function formatCookies($cookies)
{
    $cookieString = '';
    foreach ($cookies as $key => $value) {
        $cookieString .= "$key=$value; ";
    }
    return rtrim($cookieString, '; ');
}

// Function to set cookies for the cURL session
function setCookies($ch, $cookies)
{
    $cookieString = formatCookies($cookies);
    curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
}

// Open a Session with EnginFrame and get the CSRF token
// Returns an array with 'token' and 'cookies' keys
function getSession($efp_endpoint, $debug)
{
    try {
        // get JSESSIONID
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$efp_endpoint/enginframe/vdi/vdi.xml?_uri=//com.enginframe.interactive/list.sessions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignore SSL validation
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Ignore SSL host validation

        // Execute initial request to get cookies
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);

        $initialResponse = curl_exec($ch);
        $responseHeaders = explode("\r\n", $initialResponse);
        $cookies = extractCookies($responseHeaders);

        // Set cookies for the next request
        setCookies($ch, $cookies);

        // Execute actual request
        // to get CSRF token
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, "$efp_endpoint/enginframe/CsrfGuardServlet");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignore SSL validation
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Ignore SSL host validation
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Referer: $efp_endpoint/enginframe",
            "FETCH-CSRF-TOKEN: 1"
        ]);

        // Execute cURL request
        $response = curl_exec($ch);
        if ($debug) {
            echo "Curl response to get CSRF token: <br>";
            print_r($response);
        }

        // Use regular expression to extract the token
        if (preg_match('/anti-csrftoken-a2z:([A-Z0-9-]+)/', $response, $matches)) {
            $token = $matches[1];
            // Return both the token and cookies
            return ['token' => $token, 'cookies' => $cookies];
        } else {
            echo "Token not found\n";
            die();
        }
    } catch (Exception $e) {
        // Handle the exception
        echo "An error occurred: " . $e->getMessage();
        // Optionally log the error
        error_log("cURL error: " . $e->getMessage());
    } finally {
        // Close cURL handle if it was successfully initialized
        if (isset($ch) && $ch !== false) {
            curl_close($ch);
        }
    }
}

// function to execute the Login into EnginFrame
function doLogin($efp_token, $accessToken, $efp_encrypted_user, $efp_encrypted_pass, $cookies)
{
    $user_token = $accessToken;
    $username = htmlspecialchars($efp_encrypted_user, ENT_QUOTES, 'UTF-8');
    $password = htmlspecialchars($efp_encrypted_pass, ENT_QUOTES, 'UTF-8');
    if (isset($cookies["JSESSIONID"])) {
        setcookie('JSESSIONID', $cookies["JSESSIONID"], time() + 3600, '/');
    }

    echo '
    <form id="redirectForm" method="post" action="##EFPENDPOINT##enginframe/vdi/vdi.xml?_uri=//com.enginframe.interactive/list.sessions">
      <!--         <form id="redirectForm" method="post" action="##EFPENDPOINT##enginframe/vdi/vdi.admin.xml?_uri=//vdi.admin/manage.services">  -->
      <input type="hidden" name="_username" value="' . $username . '">
      <input type="hidden" name="_password" value="' . $password . '">
      <input type="hidden" name="anti-csrftoken-a2z" value="' . htmlspecialchars($efp_token) . '">
    </form>
    <script>
      document.getElementById("redirectForm").submit();
    </script>';
    exit();
}

if ($debug) {
    error_log("Session variables: " . print_r($_SESSION, true));
}

if (!isset($_SESSION['access_token'])) {
    error_log("Access token not found in session");
    header('Location: error.php');
    exit;
} else {
    $accessToken = $_SESSION['access_token'];
}


if ($debug) {
    error_log("Access Token: " . $accessToken);
}


// Verify the token
$oidc = new OpenIDConnectClient(
    '##OIDCPROVIDERURL##',
    '##CLIENTID##',
    '##CLIENTSECRET##'
);

$oidc->setVerifyHost(false);
$oidc->setVerifyPeer(false);
$oidc->setRedirectURL('https://##OIDCAPACHEDOMAIN##/##SSOPATH##/##PHPCALLBACK##');

$oidc->setProviderURL('##OIDCPROVIDERURL##');
$oidc->providerConfigParam([
    'issuer' => '##ISSUERENDPOINT##',
    'authorization_endpoint' => '##AUTHENDPOINT##',
    'token_endpoint' => '##TOKENENDPOINT##',
    'userinfo_endpoint' => '##USERINFOENDPOINT##',
    'jwks_uri' => '##JWKSENDPOINT##',
]);

if ($debug) {
    echo "Debug information:<br>";
    echo "Access Token: " . $accessToken . "<br>";
    echo "Provider URL: " . $oidc->getProviderURL() . "<br>";
    echo "Redirect URL: " . $oidc->getRedirectURL() . "<br>";
    echo "<pre>";
    print_r($oidc);
    echo "</pre>";
}

$oidc->setAccessToken($accessToken);

if ($debug) {
    $result = validateTokenFormat($accessToken, 2500);

    echo "Token validation result: " . ($result ? "true" : "false") . "\n";
    echo "Token length: " . strlen($accessToken) . "\n";
}

try {
    $userInfo = $oidc->requestUserInfo();
    try {
        // Validate token format
        if (validateTokenFormat($accessToken, 2500)) {

            // Check token validity and get preferred_username
            $preferred_username = checkTokenAndGetPreferredUsername($accessToken);

            if ($debug) {
                echo "preferred_username found was: >>> " . $preferred_username . " <<<.";
                $preferred_username = "1234567890";
                echo "preferred_username fake will be used: " . $preferred_username;
            }

            // Token is valid and we got the preferred_username
            if ($preferred_username) {
                $efp_endpoint = "##EFPENDPOINT##";
                $efp_user = $preferred_username;
                $efp_pass = $accessToken;
                date_default_timezone_set('UTC');
                $current_time = date('Y-m-d H:i:s');
                $efp_pass .= ";" . $current_time;

                $key = '##EFPAUTHSECRETKEY##';
                $nonce = '##EFPAUTHNONCE##';
                $efp_encrypted_user = encrypt($efp_user, $key, $nonce);
                $efp_encrypted_pass = encrypt($efp_pass, $key, $nonce);

                // Get EFP token and cookies
                $sessionData = getSession($efp_endpoint, $debug);
                $efp_token = $sessionData['token'];
                $cookies = $sessionData['cookies'];

                if ($debug) {
                    echo "EFP Token: >>> " . $efp_token . " <<<. <br>";
                    echo "efp_encrypted_user: >>> " . $efp_encrypted_user . " <<<. <br>";
                    echo "efp_encrypted_pass: >>> " . $efp_encrypted_pass . " <<<. <br>";
                    echo "JSESSIONID: >>> " . (isset($cookies['JSESSIONID']) ? $cookies['JSESSIONID'] : 'not set') . " <<<. <br>";
                }

                doLogin($efp_token, $accessToken, $efp_encrypted_user, $efp_encrypted_pass, $cookies);
            } else {
                // Token is invalid or we couldn't get the preferred_username
                echo "Error: Invalid token or unable to retrieve preferred_username";
            }
        } else {
            echo "Error: Invalid token format";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
