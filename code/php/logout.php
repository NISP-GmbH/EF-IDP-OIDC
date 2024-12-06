<?php
session_start();

// Clear the session
session_unset();
session_destroy();

// Redirect to the IDP's logout endpoint
$logoutUrl = '##OIDCPROVIDERURL##/protocol/openid-connect/logout';
header('Location: ' . $logoutUrl);
exit;
