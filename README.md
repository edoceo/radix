# Radix PHP Toolkit

It's a simple PHP toolkit (or framework?) that provides a very basic MVC-like environment.
It also includes basic tools for caching (file, memcache), databases (postgresql,mysql,sqlite,couchdb,mongodb), shopping carts and other wrappers for common.
API interfaces exist for dozens of services such as Facebook, eNom, Twitter, Twilio.

 * Official Site: http://edoceo.com/radix
 * API/SDK Documentation: http://edoceo.com/radix/api/
 * Git: git clone https://github.com/edoceo/radix.git
 * Packagist: https://packagist.org/packages/edoceo/radix

## History

I started hacking PHP in 2001 or so; started cobbling together useful libraries as I built them.
I read PoEAA when it came out and sort of abandoned Radix while exploring other frameworks or CMS (Cake, Drupal, Joomla, Slim, Symphony, Yii, ZF)
I like them and use them on a weekly basis but sometimes, one just has to smash out a quick web-app, that is what Radix is for.

Some use-cases I kept coming back to, where those didn't fit

 * Exisiting Custom Apps needing improvement
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

## Radix MVC Structures

Radix has a pretty typical directory structure, very simple

 * ./block - Blocks (view-partials)
 * ./controller - Controllers
 * ./etc - Configuration
 * ./layout - Layout Files
 * ./lib - Libraries or Models
 * ./vendor - Vendor supplied Libraries (via Composer)
 * ./view - View Scripts

 * ./boot.php
 * ./webroot/front.php

## Radix Provided Interfaces

 * Basics: Caching, IPC, MRU, Session

 * Auth with HTTP, Facebook, Google or Twitter
 * Checkout: Authorize.net, MerchantE, Stripe, VirtualMerchant
 * Apps: FreeSWITCH
 * Services: eNom, Twilio, Phaxio, Plivo
 * Misc: ULID
 * Email: SMTP, IMAP
 * Network: Telnet, IRC, XMPP

## Todo

 https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md