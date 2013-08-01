#!/bin/bash
# @file
# @brief Radix Helper

app_dir=$(pwd)
radix_dir=$(dirname $(readlink -f $0))

function find_uidgid()
{
    # try httpd
    chk=$(getent passwd httpd |cut -d: -f1)
    if [ -n "$chk" ]
    then
        echo "httpd:httpd"
        return
    fi

    # try www-data
    chk=$(getent passwd www-data |cut -d: -f1)
    if [ -n "$chk" ]
    then
        echo "www-data:www-data"
        return
    fi

    # default to apache
    echo "apache:apache"
}

#
# Directory Structure
mkdir \
    ./block \
    ./bin \
    ./controller \
    ./etc \
    ./lib \
    ./sbin \
    ./theme \
    ./var \
    ./vendor \
    ./view \
    ./webroot

uidgid=$(find_uidgid)
chown -R "$uidgid" "./var"

#
# Make a Bootstrapper
cat > boot.php <<EOP
<?php
/**
    @file
    @brief Radix Application Boot Strapper
*/

\$path = array();
\$path[] = '$radix_dir'; // Include Radix
\$path[] = dirname(__FILE__).'/lib'; // Include Application Libs
\$path[] = get_include_path(); // Include System Libs
set_include_path(implode(PATH_SEPARATOR,\$path));

define('APP_NAME','MyApp');
define('APP_SITE','http://myapp.com');
define('APP_ROOT',dirname(__FILE__));
define('APP_SALT','MyApp Has a Secret to Keep'); // if changed previously Salted things will not match

// Radix
require_once('radix.php');
require_once('Radix/Session.php');
radix_session::init(array('name'=>APP_NAME));

require_once('Radix/Cache.php');
// Radix_Cache::init(array('path'=>APP_ROOT . '/var/cache'));
// Radix_Cache::init(array('host'=>'localhost:11211'));

// Include some Database?
require_once('Radix/db/sql.php');
// require_once('Radix/db/mongo.php');
// require_once('Radix/Cache.php');
// require_once('Radix/Cache.php');

// Include your Libraries (from ./lib)

// Some Global Functions
function html(\$x) { return htmlentities(\$x,ENT_QUOTES,'UTF-8',false); }
function _encrypt(\$x) { return _enbase64url(mcrypt_encrypt(MCRYPT_RIJNDAEL_256,APP_SALT,\$x,MCRYPT_MODE_ECB)); }
function _decrypt(\$x) { return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256,APP_SALT,_debase64url(\$x),MCRYPT_MODE_ECB)); }
function _enbase64url(\$x) { return rtrim(strtr(base64_encode(\$x), '+/', '-_'), '='); }
function _debase64url(\$x) { return base64_decode(str_pad(strtr(\$x, '-_', '+/'), (strlen(\$x) % 4), '=', STR_PAD_RIGHT)); }

/*
    Include some Application Global Functions Here
    Maybe initialise \$_ENV
*/

EOP

#
# Make a Theme
cat >./theme/html.php <<EOP
<?php
header('Content-Type: text/html; charset="utf-8"');

?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta name="description" content="">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="/css/app.css">
<script src="/js/app.js"></script>
<title><?php echo \$_ENV['title']; ?></title>
</head>
<body>
<?php
echo radix_session::flash();
echo \$this->body
?>
</body>
</html>

EOP

cat >./webroot/index.php <<EOP
<?php

require_once(dirname(dirname(__FILE__)) . '/boot.php');

radix::init(\$opts);
// radix::\$view->mdb = new radix_db_mongo();
// radix::route('some/path?or=pattern','/real/action');
radix::exec();
radix::view();
radix::send();

EOP

cat >apache.conf.example <<EOC

<Directory "$app_dir">

    RewriteRule
    

    php_value
    php_flag

    php_admin_flag
    php_admin_value

</Directory>

EOC

cat >nginx.conf.radix <<EOC
server {

}
EOC