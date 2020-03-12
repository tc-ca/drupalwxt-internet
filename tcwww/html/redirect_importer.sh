#!/bin/bash

i=0
cat redir.csv| while read line || [ -n "$line" ]
do
 let "i++"
 rsource=`echo $line | cut -d ',' -f1`
 rtarget=`echo $line | cut -d ',' -f2`
 redirect='use Drupal\redirect\Entity\Redirect;Redirect::create(["redirect_source" => "'$rsource
 if [[ $rtarget = *"http"* ]]; then
   redirect+='", "redirect_redirect" => "'$rtarget
 else
   redirect+='", "redirect_redirect" => "internal:'$rtarget
 fi
 redirect+='", "language" => "und", "status_code" => "301",])->save();'
 #echo $redirect
 drush ev "$redirect"
 echo $i $rsource' to ' $rtarget ' added.'
done