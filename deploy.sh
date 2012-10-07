# Deployement for tuneefy
#!/bin/bash

YELLOW='\033[01;33m'  # bold yellow
RED='\033[01;31m' # bold red
GREEN='\033[01;32m' # green
BLUE='\033[01;34m'  # blue
RESET='\033[00;00m' # normal white

echo -e ""${RESET}
echo " # ------------------------- #"
echo " # panorame promotion script #"
echo " # ------------------------- #"
echo ""

if [ $# -lt 1 ]
then
  echo " Usage: $0 (poc) [file1 file2 ...]";
  echo ""
  exit 1;
fi

# Checking environment is good
env=$1;

case $env in
   "poc") real_env="poc";;
   *) echo -e " # "${RED}"ERROR"${RESET}" : Bad environment ($env)"
      echo ""
      exit 1;;
esac

if [ $# -ge 2 ]
then

  files=""

  # Checking we have files
  for x in "$@"; do 
    if [ -e $PWD"/"$x ]
    then
      files=$files" "$x;
    fi
  done

  files=$(sed -e 's/^[[:space:]]*//' <<<"$files")

  echo -e " #"${GREEN}" Promoting "${RESET}${real_env}" environment partially to current release"
  echo -e " #  "${YELLOW}"\_Files : "${files} 

  # Rsync local
  echo -e ${BLUE}
  rsync -RlptgoDvz ${files} -e 'ssh -p 1122' . "tchap@ismerging.us:/home/tchap/www/panorame/" --exclude-from 'exclude.rsync'
  echo -e ${RESET}

  echo -e " # "${GREEN}"Done. "${RESET}
  echo ""

else
  echo -e " #"${GREEN}" Promoting "${RESET}${real_env}" environment to current release"

  echo -e ${BLUE}
  # Rsync global
  rsync -rlptgoDvz -e 'ssh -p 1122' . "tchap@ismerging.us:/home/tchap/www/panorame/" --exclude-from 'exclude.rsync'
  echo -e ${RESET}  

  echo -e " # "${GREEN}"Done."${RESET}
  echo ""
fi

# Building Minified JS
# while true; do
#     read -p " # -- Do you wish to build minified Javascript [no] ?" yn
#     case $yn in
#         [Yy]* ) echo ""
#     break;;
#         [Nn]* ) echo -e " # "${GREEN}"Done. "${RESET}"Exiting."
#     echo ""
#     exit;;
#         * ) echo -e " # "${GREEN}"Done. "${RESET}"Exiting."
#     echo ""
#     exit 1;;
#     esac
# done


# echo -e " # "${GREEN}"Building"${RESET}" minified JS"

# mv js/min/tuneefy.min.js js/min/tuneefy.min.js.backup

# echo -e ${BLUE}
# if [ $real_env = "prod" ]
# then
#   curl -o js/min/tuneefy.min.js 'http://beta:tuneefy2011@beta.tuneefy.com/admin/minify.php?adv=0&include_build_version=true'
# else
#   curl -o js/min/tuneefy.min.js 'http://beta:tuneefy2011@beta.tuneefy.com/admin/minify.php?include_build_version=true&adv=0'
# fi

# wait $!

# echo -e ${RESET}

# echo -e " # "${GREEN}"Done. "${RESET}
# echo ""

# echo -e " # "${GREEN}"Updating "${RESET}" minified JS"
# echo -e ${BLUE}
# rsync --verbose -RlptgoDvz 'js/min/tuneefy.min.js' -e 'ssh -p 1122' . "tchap@ismerging.us:/home/tchap/www/tuneefy/"${real_env}"/"
echo -e ${RESET}
echo -e " # "${GREEN}"Done. "${RESET}"Exiting."
echo ""

