# Radix PHP Toolkit

Radix is a PHP toolkit that I originally started in 2001, and it's been gradually growing ever since.
It provides very minimalistic structures for things like MVC as well as some common interfaces and APIs to external interfaces.

## Radix MVC Structures

Radix has a pretty hard coded method for it's MVC

* Controllers: ./c
* View: ./v

* Libraries or Models typically in ./lib

## Bootstrap the MVC

<pre>

// Path

require_once('Radix.php');

radix::init()
radix::exec();
radix::view();
radix::send();

</pre>

## Radix Provided Interfaces

* Basics: Caching, Cart, IPC, MRU, Session

* Auth with HTTP, Facebook,  Google or Twitter
* Checkout: Authorize.net, MerchantE, VirtualMerchant
* Apps: FreeSWITCH
* Misc API: eNom, Twilio
* Amazon: S3, MTurk
* Email: SMTP, IMAP
* Network: Telnet, IRC, XMPP
