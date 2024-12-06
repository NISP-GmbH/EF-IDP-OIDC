#!/bin/bash

ef_auth_file=$(find /opt/nisp/ -iname ef.auth | egrep -i "enginframe/plugins/pam/bin")

if [[ "${ef_auth_file}x" != "x" ]]
then
    if [ ! -f ${ef_auth_file}.original.backup ]
    then
        cp ${ef_auth_file} ${ef_auth_file}.original.backup
    fi

    if [ -f ${ef_auth_file}.original.backup ]
    then
        if [ -f code/efp/ef.auth ]
        then
            cp -f code/efp/ef.auth ${ef_auth_file}
        else
            cp -f ef.auth ${ef_auth_file}
        fi
        chmod +x ${ef_auth_file}
    fi
else
    echo "Important: >>> ef.auth <<< can not be found. You need to copy code/efp/ef.auth and replace the file: /opt/nisp/enginframe/2024.0-r1786/enginframe/plugins/pam/bin/ef.auth"
fi

ef_auth_conf_file=$(find /opt/nisp -iname ef.auth.conf | egrep -i "enginframe/plugins/pam/conf")

if [[ "${ef_auth_conf_file}x" != "x" ]]
then
    sed -i 's/^EFAUTH_USERMAPPING.*/EFAUTH_USERMAPPING="true"/' "${ef_auth_conf_file}"
    pam_path=$(dirname "$(dirname "$ef_auth_conf_file")")

    if [ ! -f ${pam_path}/bin/ef.user.mapping ]
    then
        cat <<EOF> ${pam_path}/bin/ef.user.mapping
#!/bin/bash

echo "efadmin"
EOF
        chmod +x ${pam_path}/bin/ef.user.mapping
    fi
else
    echo "Important: >>> ef.auth.conf file <<< file can not be found. You need to edit /opt/nisp/enginframe/VERSION/enginframe/plugins/pam/conf/ef.auth.conf and set to true the variable: EFAUTH_USERMAPPING=\"true\""
    echo "Important: >>> ef.user.mapping <<< file can not be found. Creating the file, so you can replace >>> ef.user.mapping <<< into /opt/nisp/enginframe/VERSION/enginframe/plugins/pam/bin/ef.user.mapping"
    cat <<EOF> ./ef.user.mapping
#!/bin/bash

echo "efadmin"
EOF
fi
