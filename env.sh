#!/bin/bash
while read line
do
   heroku config:set $line
done < .env
