#!/bin/bash

server_conf=$(find /opt/nisp/ -iname server.conf | egrep -i "enginframe/conf/server.conf")

if [ -f $server_conf ]
then
    # For ef.filter.csrf.tokenCheck
    if grep -q "^ef\.filter\.csrf\.tokenCheck=" "$server_conf"
    then
        sed -i 's/^ef\.filter\.csrf\.tokenCheck=.*/ef.filter.csrf.tokenCheck=false/' "$server_conf"
    else
        echo "ef.filter.csrf.tokenCheck=false" >> "$server_conf"
    fi

    # For ef.filter.csrf.allowAccessWithNoOrigin
    if grep -q "^ef\.filter\.csrf\.allowAccessWithNoOrigin=" "$server_conf"
    then
        sed -i 's/^ef\.filter\.csrf\.allowAccessWithNoOrigin=.*/ef.filter.csrf.allowAccessWithNoOrigin=true/' "$server_conf"
    else
        echo "ef.filter.csrf.allowAccessWithNoOrigin=true" >> "$server_conf"
    fi

    # For ef.filter.csrf.targetOrigins
    if grep -q "^ef\.filter\.csrf\.targetOrigins=" "$server_conf"
    then
        sed -i 's|^ef\.filter\.csrf\.targetOrigins=.*|ef.filter.csrf.targetOrigins=https://##OIDCAPACHEDOMAIN##|' "$server_conf"
    else
        echo "ef.filter.csrf.targetOrigins=https://##OIDCAPACHEDOMAIN##,http://##OIDCAPACHEDOMAIN##" >> "$server_conf"
    fi

    sudo systemctl stop enginframe
    sudo systemctl start enginframe
else
    echo "Important: >>> server.conf <<< can not be found. You need to edit the file >>> /opt/nisp/enginframe/VERSION/enginframe/conf/server.conf <<< and set:"
    echo "ef.filter.csrf.tokenCheck=false"
    echo "ef.filter.csrf.allowAccessWithNoOrigin=true"
    echo "ef.filter.csrf.targetOrigins=https://##OIDCAPACHEDOMAIN##"
fi
