#!/bin/bash
#
# EndNote - Importer to RefBase MySQL table
#
# Andreas Czerniak <ac@oceanlibrary.org>
#
# initial: 05-11-2005
#
# modified:
# 2005-12-11 ; ac ; clean up static codes
# 2005-12-15 ; rk ; remove "v.9", import into CVS
#

if [ $# -lt 1 ]; then
  echo "Which endnote file ?"
  echo -e "\nusage: $0 endnote.file [database [path-to-mysql [mysql-options] ] ]\n"
  exit 127
fi

$ENFILE=$1

$MYSQLDB=$2     || $MYSQLDB="literature"          # default: literature
$MYSQLPATH=$3   || $MYSLPATH="/var/lib/mysql"     # default: /var/lib/mysql
$MYSQLOPTION=$4 || $MYSQLOPTION="-p"		  # default: with password

if [ ! -d imported ] ; then
  mkdir imported
fi

./endnote2mysql.php $1

if [ ! -f import.txt ] ; then
  echo "endnote2mysql convert failed !"
  exit 0
fi
cp import.txt $MYSQLPATH/$MYSQLDB/

mysql $MYSQLOPTION $MYSQLDB < loadimport.sql > sqloutput.txt

cat sqloutput.txt

rm $MYSQLPATH/$MYSQLDB/import.txt
rm import.txt
rm sqloutput.txt

cat $ENFILE | tail

echo "\n\nrows imported: "
cat $ENFILE | wc -l

mv $ENFILE imported/.

