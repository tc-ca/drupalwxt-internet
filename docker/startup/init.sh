#!/bin/bash
cat >/etc/motd <<EOL 
  _____                               
  /  _  \ __________ _________   ____  
 /  /_\  \\___   /  |  \_  __ \_/ __ \ 
/    |    \/    /|  |  /|  | \/\  ___/ 
\____|__  /_____ \____/ |__|    \___  >
        \/      \/                  \/ 
A P P   S E R V I C E   O N   L I N U X
Documentation: http://aka.ms/webapp-linux
EOL
cat /etc/motd

eval $(printenv | sed -n "s/^\([^=]\+\)=\(.*\)$/export \1=\2/p" | sed 's/"/\\\"/g' | sed '/=/s//="/' | sed 's/$/"/' >> /etc/profile)

#Create apache log directory
mkdir -p $APACHE_LOG_DIR

files_dir="${DRUPAL_STORAGE_DIR}/html/sites/default/files"
config_dir="${DRUPAL_STORAGE_DIR}/config"

#Create drupal directories and symlink
mkdir -p $files_dir
mkdir -p $config_dir

files_link=/var/www/html/sites/default/files
if ! [ -L $files_link ]; then
  ln -sf $files_dir $files_link
fi

config_link=/var/www/config
if ! [ -L $config_link ]; then
  ln -sf $config_dir $config_link
fi

service ssh start

exec apache2ctl -DFOREGROUND "$@"