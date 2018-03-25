#!/bin/bash
# Run this script from the guzzle folder to update Guzzle
# Update version.php verion and release
# Manually test that the GUzzle can be used with no issues.

GUZZLEDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo Enter version number of Guzzle to update to. e.g. 6.3.0
read varversion


find $GUZZLEDIR -mindepth 1 -maxdepth 1 | grep -v readme_moodle.txt | xargs rm -rf
wget -O $GUZZLEDIR/guzzle.zip "https://github.com/guzzle/guzzle/releases/download/$varversion/guzzle.zip"
unzip $GUZZLEDIR/guzzle.zip -d $GUZZLEDIR
sed -i -e 's/require/require_once/g' $GUZZLEDIR/autoloader.php
sed -i -e 's#GuzzleHttp/functions.php#GuzzleHttp/functions_include.php#g' $GUZZLEDIR/autoloader.php
sed -i -e 's#GuzzleHttp/Psr7/functions.php#GuzzleHttp/Psr7/functions_include.php#g' $GUZZLEDIR/autoloader.php
sed -i -e 's#GuzzleHttp/Promise/functions.php#GuzzleHttp/Promise/functions_include.php#g' $GUZZLEDIR/autoloader.php
rm $GUZZLEDIR/guzzle.zip
echo
echo 'Go update version.php'
echo $GUZZLEDIR
