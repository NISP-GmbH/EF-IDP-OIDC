#!/bin/bash

echo "#####################################"
echo "Checking all files and services..."

if systemctl is-active --quiet httpd
then
    echo "OK : Apache is running"
else
    echo "ERROR : Apache is not running"
fi


