#!/bin/bash

for installer_script in install_packs.sh replace_strings.sh install_ssl.sh install_webserver.sh install_efauth.sh
do
    if echo $installer_script | egrep -iq "replace_strings"
    then
        /bin/bash ${installer_script}
        return_code=$?
    else
        /bin/bash code/bash/${installer_script}
        return_code=$?
    fi

    if [ $return_code -ne 0 ]
    then
        echo "Failed with error code >>> $return_code <<< during the script >>> $installer_script <<< execution. Exiting..."
        exit 1
    fi

    if [ -f compose.tgz ]
    then
        sudo tar -xzvf compose.tgz -C /var/www/html/
    fi
done
