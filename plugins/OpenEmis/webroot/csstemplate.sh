#!/bin/bash

#####################################################################################################################
# File:     csstemplate.sh
# Version:  1.0
# Purpose:  Replacing a product layout files to layout.template.css with shell command
#           with the required placeholder 
# 
# How to use: 
#  - Navigate to the folder containing this file in the styleguide project
#  - Type "./csstemplate.sh" with the list of arguments behind
# 
# Pass in arguments to replace the primary color, secondary color and background image for
# the template for what product. Default run with no arguments will create template for
# core.
# 
# -----------------------------------------------------------------------------------------------
# | Arguments  |  Description                                                                   |
# -----------------------------------------------------------------------------------------------
# | -product   |  Specify what product to create the custom templating. Default to core.        |
# | -pcolor    |  Primary color replacement to placeholder "${prodColor}". Do not need hex (#)  |
# | -scolor    |  Secondary color replace to placeholder "${secondColor}". Do not need hex (#)  | 
# | -bgimg     |  Background-image url replace to placeholder "${bgImg}"                        |
# -----------------------------------------------------------------------------------------------
# 
# E.g. Templating for dashboard: 
#   - Product:          dashboard
#   - Product color:    990000 (short: 900)
#   - Secondary color:  6b0000
#   - Background:       ../../../img/dashboard-login-bg.jpg
#   
# Command to run: 
# "./csstemplate.sh -product dashboard -pcolor 900 -scolor 6b0000 -bgimg ../../../img/dashboard-login-bg.jpg"
#####################################################################################################################

# Styleguide Version
STYLEGUIDE="2.7.3";

# Product variables
PRODUCT="core";
COLOR="69c";
SECCOLOR="476B8F";
BGLINK="../../../img/core-login-bg.jpg";

# Placeholder variables
PCOLOR='${prodColor}';
SCOLOR='${secondColor}';
BGIMG='${bgImg}';

# File paths
THEMEPATH="css/themes";
CUSTOMPATH="$THEMEPATH/custom";

# Replace functions
ReplaceSixCharacterColor()
{
    lowerchar=`echo $1 | tr '[:upper:]' '[:lower:]'`;
    upperchar=`echo $1 | tr '[:lower:]' '[:upper:]'`;

    sed -i '' 's~'$lowerchar'~'$2'~g' $3;
    sed -i '' 's~'$upperchar'~'$2'~g' $3;

    unset lowerchar;
    unset upperchar;
}

ReplaceThreeCharacterColor()
{
    # Replace 6 character color before 3 - Prevent replace wrongly.
    firstlower=`echo "${1:0:1}" | tr '[:upper:]' '[:lower:]'`;
    secondlower=`echo "${1:1:1}" | tr '[:upper:]' '[:lower:]'`;
    thirdlower=`echo "${1:2:1}" | tr '[:upper:]' '[:lower:]'`;
    firstupper=`echo "${1:0:1}" | tr '[:lower:]' '[:upper:]'`;
    secondupper=`echo "${1:1:1}" | tr '[:lower:]' '[:upper:]'`;
    thirdupper=`echo "${1:2:1}" | tr '[:lower:]' '[:upper:]'`;

    sed -i '' 's~[\#]\(['$firstlower']\)\1\{1\}\(['$secondlower']\)\2\{1\}\(['$thirdlower']\)\3\{1\}~'$2'~g' $3;
    sed -i '' 's~[\#]\(['$firstupper']\)\1\{1\}\(['$secondupper']\)\2\{1\}\(['$thirdupper']\)\3\{1\}~'$2'~g' $3;
    sed -i '' 's~[\#]\(['$firstlower']\)\1\{0\}\(['$secondlower']\)\2\{0\}\(['$thirdlower']\)\3\{0\}~'$2'~g' $3;
    sed -i '' 's~[\#]\(['$firstupper']\)\1\{0\}\(['$secondupper']\)\2\{0\}\(['$thirdupper']\)\3\{0\}~'$2'~g' $3;

    unset firstlower;
    unset secondlower;
    unset thirdlower;
    unset firstupper;
    unset secondupper;
    unset thirdupper;
}

ReplaceColor() 
{
    if [ ${#1} -eq 3 ]; then
        ReplaceThreeCharacterColor $1 $2 $3;
    elif [ ${#1} -eq 6 ]; then
        ReplaceSixCharacterColor $1 $2 $3;
    else
        echo "Invalid color code specify: $1";
    fi
}

CheckCustomDir()
{
    # Creates the custom folder in OpenEmis/webroot/css/themes/custom if doesn't exist
    if [ ! -d $CUSTOMPATH ]; then
        mkdir $CUSTOMPATH;
    fi
}

MinifyLayout()
{
    # Minify the product layout files
    if ! (  minify "./$PRODUCTPATH/layout.css" &&  
            minify --no-comments --output "./$PRODUCTPATH/temp.css" "./$PRODUCTPATH/layout.css" ) &>/dev/null; then
        echo "Error occured when trying to minify product: $PRODUCT";
        exit 0;
    fi
}

CopyCustom()
{
    # Remove the template file if the file existed before copying
    if [ -f $TEMPLATEPATH ]; then
        rm $TEMPLATEPATH;
    fi

    # Copy the minified file to custom folder
    if ! ( mv "./$PRODUCTPATH/temp.css" $TEMPLATEPATH) &>/dev/null; then
        echo "Error occured when trying to move css file to custom folder";
        exit 0;
    fi
}

ProcessTemplate()
{
    # Replacing the colors and bg image to placeholder
    sed -i '' 's~'$BGLINK'~'$BGIMG'~g' $TEMPLATEPATH;
    ReplaceColor $COLOR $PCOLOR $TEMPLATEPATH;
    ReplaceColor $SECCOLOR $SCOLOR $TEMPLATEPATH;

    # Add comments to the start of the file
    echo -e "/*\n    Styleguide Version: $STYLEGUIDE\n*/" | cat - $TEMPLATEPATH > temp && mv temp $TEMPLATEPATH;
}

#####################################################################################################################
# Main function starts here
#####################################################################################################################
# Get arguments from command and process
iterator=1;
nextIter=2;
if [ $# -gt 0 ]; then
    for argument in $@
    do 
        case $argument in
            "-pcolor")
                COLOR=${!nextIter}
                ;;
            "-product")
                PRODUCT=${!nextIter}
                ;;
            "-scolor")
                SECCOLOR=${!nextIter}
                ;;
            "-bgimg")
                BGLINK=${!nextIter}
                ;;
        esac
        let iterator=iterator+1;
        let nextIter=iterator+1;
    done
fi

PRODUCTPATH="$THEMEPATH/$PRODUCT";
TEMPLATEPATH="./$CUSTOMPATH/layout.$PRODUCT.template.css";

CheckCustomDir;
MinifyLayout;
CopyCustom;
ProcessTemplate;

echo "> layout.$PRODUCT.template.css added successfully.";
