#!/bin/bash bash

RED='\033[0;31m'
NC='\033[0m' # No Color

echo "Updating git repository $1 / $2"

git fetch origin
git reset --hard origin/master

if test $(find "./app/db/GeoLite2/GeoLite2-Country.mmdb" -mmin +259200)
then
    printf "${RED}GEO country DB has not been updated for more than 6 months. Go to https://dev.maxmind.com/geoip/geoip2/geolite2/ for more info${NC}\n"
fi

echo 'Starting build...'

docker build --build-arg VERSION="$2" --tag appwrite/appwrite:"$1" .

echo 'Pushing build to registry...'

docker push appwrite/appwrite:"$1"
