#!/bin/bash
echo "Updating permissions"
php tools/perm.php permissions.xml plugin.yml src DHMO\\XialotEcon\\Permissions
source paths.sh
echo "def sql"
php "${libasynql}"/cli/def.php def src DHMO\\XialotEcon\\Database\\Queries --struct final\ class --eol LF --sql resources
echo "x2j kinetic.xml"
php "${libkinetic}"/cli/x2j.php x2j resources/kinetic.xml resources/kinetic.json min
echo "def kinetic.xml"
php "${libkinetic}"/cli/def.php def resources/kinetic.xml src DHMO\\XialotEcon\\KineticIds struct final\ class eol LF
echo "def lang"
php "${libglocal}"/cli/def.php def resources/lang/en_US.yml src DHMO\\XialotEcon\\Lang struct final\ class eol LF
