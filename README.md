# Radix PHP Toolkit

It's a simple PHP toolkit (or framework?) that provides a basic MVC environment
It also includes basic tools for caching (file, memcache), databases (postgresql,mysql,sqlite,couchdb,mongodb), shopping carts and other things you'd expect to find.
API interfaces exist for dozens of services such as AWS, Facebook, eNom, Twitter, Twilio.

* Official Site: http://radix.edoceo.com/
* API Documentation: http://radix.edoceo.com/dox

## History

I started hacking PHP in 2001 or so; started cobbling together useful libraries as I built them.
I read PoEAA when it came out and sort of abandoned Radix while exploring other frameworks or CMS (Cake, Drupal, Joomla, Symphony, ZF ...)
I like them, will embrace them and use them on a weekly basis.

However, some use-cases I kept coming back to, where those didn't fit

* Exisiting Custom Apps
* Rapid Prototype / LoFi MVP

### Existing Apps

There are many custom web-applications that have been around for a while and currently don't use any existing framework.
These kinds of applications suffer internal inconsistencies and many lack documentation.

These can use Radix as a unified and documented set of tools.
Linking to Radix, you can use portions of it (ACL, Cache, Social APIs) with minimal dependencies.
This brings some stability to legacy applications w/o requiring a more lenghty overhaul as when converting to a more robust framework platform.

### Rapid Prototype / MVP

Also, in many cases we need to build rapid prototypes to test business models; or build one-off internal applications.
Due to it's simplistic nature Radix can be used to create these systems pretty quickly.

## We Don't Have

* Automatic table creation from some magic markup
* Fancy inherited modeling
* Command line tools

## Radix MVC Structures

Radix has a pretty hard coded method for it's MVC

* ./boot.php
* ./controller - Controllers
* ./etc - Configuration
* ./lib - Libraries or Models
* ./view - Guess!
* ./webroot/index.php
'
## Radix Provided Interfaces

* Basics: Caching, Cart, IPC, MRU, Session

* Auth with HTTP, Facebook, Google or Twitter
* Checkout: Authorize.net, MerchantE, Stripe, VirtualMerchant
* Apps: FreeSWITCH
* Misc API: eNom, Twilio, Phaxio, Plivo
* Amazon: MTurk, S3, SQS
* Email: SMTP, IMAP
* Network: Telnet, IRC, XMPP
