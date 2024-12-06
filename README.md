# EnginFrame + IDP server using OpenIDC

This repository contain a solution to integrate EF Portal with a IDP server that is using OpenIDC standard.

## General Flow

Explained in [NI-SP Knowledge base](https://www.ni-sp.com/knowledge-base/ef-auth/ef-portal-openidc/).

## Requirements
- Rocky Linux 8/9, AlmaLinux 8/9
- Apache 2.4.37+
- Php 8.2+
- Apache2 modules: php, mod_auth_openidc, mod_ssl and mod_proxy_html
- SSL Certificates
- If external users will be used (AD/LDAP), the server need to have the users mapped

Note: Is totally possible to setup in Ubuntu Server or any other Debian based distro, however the scripts in this repository are currently designed to support RedHat based distros in version 8 and 9. You can follow the install.sh file and replace commands relative to RedHat systems to Ubuntu similar commands.

## Setup

1. You need to copy the replacements.txt file to replacements_custom.txt file. Then you need to edit the replacements_custom.txt with your current IDP configuration:

```bash
##OIDCPROVIDERURL## : Your OIDC provider URL (example: https://idp.domain.com/auth/realms/kums-mfa)
##AUTHENDPOINT## : Your OIDC auth endpoint (example: https://idp.domain.com/auth/realms/kums-mfa/protocol/openid-connect/auth)
##TOKENENDPOINT## : Your OIDC token endpoint (example: https://idp.domain.com/auth/realms/kums-mfa/protocol/openid-connect/token)
##USERINFOENDPOINT## : Your OIDC userinfo endpoint (example: https://idp.domain.com/auth/realms/kums-mfa/protocol/openid-connect/userinfo)
##ISSUERENDPOINT## : Your OIDC issuer endpoint (example: https://idp.domain.com/auth/realms/kums-mfa)
##JWKSENDPOINT## : Your OIDC JWKS endpoint (example: https://idp.domain.com/auth/realms/kums-mfa/protocol/openid-connect/certs)
##SSOPATH## sso
##EFPALIAS## site
##SSOLOGIN## auth
##PHPCALLBACK## callback.php
##OIDCAPACHEDOMAIN## : The URI, without https://, that Apache will listen (example: loginefp.domain.com)
##EFPENDPOINT## : The URL where EF Portal is running, with https:// and port (example: https://efportal.ni-sp.com:8443/
##CLIENTID## : Your OpenIDC App Client ID
##CLIENTSECRET## : Your OpenIDC App Client Secret
##RANDOMSECRETPHRASE## notused
##ADMINEMAIL## : Email of the server sysadmin
```

You can check the replacements.txt file from the git to see a good example of replacements_custom.txt.

Then you need to execute the script below to replace those strings in all git files.

```bash
replace_strings.sh
```

Note: If you did some mistake in replacements_custom.txt and you want to run replace_strings.sh again, you need to revert all git changes with the command:

```bash
git reset --hard origin/main 
```


2. Setup the IDP slution.

If EF Portal is in the same server where you intend to setup the solution, you just need to execute:

```bash
bash install.sh
```

If EF Portal is in different server than EF Portal, then you need to do a more manual, but easy, setup. Please follow the below steps.

1. Execute the scripts: install_packs.sh, install_ssl.sh and install_webserver.sh.
Note: The script install_webserver.sh will download jumbojett/openid-connect-php php library. If you do not have internet in this server, please manually download the php library and copy to the same place (/var/www/html) where the script is downloading the library.
2. You need to copy the code/bash/install_efauth.sh and code/efp/ef.auth scripts to your EF Portal server, both in the same directory.
3. Then execute the script:
```bash
install_efauth.sh
```
4. Now you need to map your remote users to local users in the file /opt/nisp/enginframe/2024.0-r1786/enginframe/plugins/pam/bin/ef.user.mapping [as explained here](https://www.ni-sp.com/knowledge-base/enginframe/tips-and-tricks/#h-mapping-ef-portal-users-to-local-linux-users). By default, all users will be mapped to "efadmin" administrator user, and probably you do not want that. This is needed because EF Portal will store some user data that needs to be isolated from the other users. Remember that your mapped local users must follow the linux username syntax estrictions of your Linux distribution.

Now your Apache was integrated to receive the user that want to access EF Portal using external IDP dashboard.

If you want to allow specific users to directly login into the interface, you can check the ef.auth script code in the bash if condition that check if the username is "efadmin", that will bypass IDP process.
