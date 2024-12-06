#!/bin/bash

if [ -f /etc/efp_oidc_parameters.conf ]
then
    echo "The file /etc/efp_oidc_parameters.conf was found."
    echo "You can copy that file and continue."
    echo "cp /etc/efp_oidc_parameters.conf replacements_custom.txt"
fi

if [ ! -f replacements_custom.txt ]
then
	echo "You need to copy the replacements.txt file to replacements_custom.txt and do your customization"
	echo "cp replacements.txt replacements_custom.txt"
    exit 1
fi

# Path to the file containing the replacements
REPLACEMENTS_FILE="replacements_custom.txt"

# List of files to perform the replacements in
FILES_TO_REPLACE=("code/apache/idp.conf" "code/bash/set_server.conf.sh" "code/bash/install_ssl.sh" "code/efp/ef.auth" "code/php/secure_page.php" "code/php/callback.php" "code/php/index.php")

# Read the replacements file line by line
while IFS= read -r line
do
  # Extract the placeholder and the replacement value
  PLACEHOLDER=$(echo $line | awk '{print $1}')
  REPLACEMENT=$(echo $line | awk '{print $2}')

  # Perform the replacements in each file
  for FILE in "${FILES_TO_REPLACE[@]}"
  do
    sed -i "s|$PLACEHOLDER|$REPLACEMENT|g" $FILE
  done
done < "$REPLACEMENTS_FILE"

for PLACEHOLDER in "##EFPAUTHSECRETKEY##" "##EFPAUTHNONCE##"
do
    REPLACEMENT=$(openssl rand -hex 16)
    for FILE in "${FILES_TO_REPLACE[@]}"
    do
        sed -i "s|$PLACEHOLDER|$REPLACEMENT|g" $FILE
    done
done

cp -f replacements_custom.txt /etc/ef_entraid_parameters.conf
chmod 640 /etc/ef_entraid_parameters.conf

echo "Replacements complete."
echo "A copy called \"ef_entraid_parameters.conf\" will be stored in /etc/"
