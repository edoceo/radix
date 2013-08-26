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

// Radix
require_once('radix.php');
require_once('Radix/Session.php');
radix_session::init(array('name'=>APP_NAME));

// Caching?
// require_once('Radix/Cache.php');
// Radix_Cache::init(array('path'=>APP_ROOT . '/var/cache'));
// Radix_Cache::init(array('host'=>'localhost:11211'));

// Include some Database?
// require_once('Radix/db/sql.php');
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
/**
    Primary Theme for the Radix Application, HTML output
*/

header('Content-Type: text/html; charset="utf-8"');

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta name="description" content="">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="//gcdn.org/radix/radix.css" rel="stylesheet" media="screen">
<!-- <link rel="stylesheet" href="/css/app.css"> -->
<!-- <script src="/js/app.js"></script> -->
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

#
# The Front Controller
cat >./webroot/index.php <<EOP
<?php
/**
    Front Controller for Application
*/

define('APP_INIT',microtime(true));
define('APP_NAME','MyApp');
define('APP_SITE','http://myapp.com');
define('APP_ROOT',dirname(__FILE__));
define('APP_SALT','MyApp Has a Secret to Keep'); // if changed previously Salted things will not match

require_once(dirname(dirname(__FILE__)) . '/boot.php');

\$opts = array();
radix::init(\$opts);
// radix::\$view->mdb = new radix_db_mongo();
// radix::route('some/path?or=pattern','/real/action');
radix::exec();
radix::view();
radix::send();

EOP

cat >apache.conf.example <<EOC

<Directory "$app_dir">

    Header unset Pragma

    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule .* /index.php [QSA,L]

    php_flag display_errors on
    php_flag display_startup_errors on

    php_value error_reporting -1

    # php_admin_flag
    # php_admin_value

</Directory>

EOC

cat >nginx.conf.radix <<EOC
server {
    location / {

        root "$app_dir";

        fastcgi_pass   127.0.0.1:9000;
        fastcgi_index  index.php;

        # fastcgi_split_path_info ^/(.+\.php)(/?.+)$;

        fastcgi_param  GATEWAY_INTERFACE  CGI/1.1;

        fastcgi_param  SERVER_ADDR        \$server_addr;
        fastcgi_param  SERVER_PORT        \$server_port;
        fastcgi_param  SERVER_NAME        \$server_name;
        fastcgi_param  SERVER_SOFTWARE    nginx/\$nginx_version;
        fastcgi_param  SERVER_PROTOCOL    \$server_protocol;

        fastcgi_param  REMOTE_ADDR        \$remote_addr;
        fastcgi_param  REMOTE_PORT        \$remote_port;

        fastcgi_param  REQUEST_METHOD     \$request_method;
        fastcgi_param  REQUEST_URI        \$request_uri;
        fastcgi_param  QUERY_STRING       \$query_string;
        # fastcgi_param  CONTENT_TYPE       \$content_type;
        # fastcgi_param  CONTENT_LENGTH     \$content_length;

        fastcgi_param  DOCUMENT_URI       \$document_uri;
        fastcgi_param  DOCUMENT_ROOT      \$document_root;

        fastcgi_param  SCRIPT_NAME        \$fastcgi_script_name;
        fastcgi_param  PATH_INFO          \$fastcgi_path_info;
        # fastcgi_param  SCRIPT_FILENAME    $app_dir/\$fastcgi_script_name;
        fastcgi_param  SCRIPT_FILENAME    $app_dir/webroot/index.php;

        # PHP only, required if PHP was built with --enable-force-cgi-redirect
        # fastcgi_param  REDIRECT_STATUS    200;

    }

}
EOC

# make_dirs;
# make_boot;
# make_conf;