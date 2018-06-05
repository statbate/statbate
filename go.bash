#!/bin/bash

if [ ! -z "$1" ]; then
echo "$1"
git add .
git commit -m "$1"
git push origin master
fi
