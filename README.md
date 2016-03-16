# mailhost-resolver

Simple php script to generate an IP address list of DNS MX and SPF entries for a given domain.

[![License](https://poser.pugx.org/rephlux/spf-resolver/license.svg)](https://packagist.org/packages/rephlux/spf-resolver)

## Purpose

To implement dynamic firewall rules (e.g. using an [URL Table alias](https://doc.pfsense.org/index.php/Aliases#URL_Table_Aliases) in [pfsense](https://www.pfsense.org/) or in [pfBlockerNG](https://forum.pfsense.org/index.php?topic=86212.0)) this scipt resolves both MX and SPF entries and presents the result as `text/plain` list. It can be used e.g. to allow traffic to and from mail servers accociated with a given domain.

### What is MX

> A mail exchanger record (MX record) is a type of resource record in the [Domain Name System](https://en.wikipedia.org/wiki/Domain_Name_System) that specifies a mail server responsible for accepting email messages on behalf of a recipient's domain, and a preference value used to prioritize mail delivery if multiple mail servers are available. The set of MX records of a domain name specifies how email should be routed with the Simple Mail Transfer Protocol (SMTP).

Source: [Wikipedia](https://en.wikipedia.org/wiki/MX_record), retreived 16.03.2016


### What is SPF

> The Sender Policy Framework (SPF) is an open standard specifying a technical method to prevent sender address forgery. More precisely, the [current version](http://www.openspf.org/Specifications) of SPF — called _SPFv1_ or _SPF Classic_ — protects the envelope sender address, which is used for the delivery of messages. See the box on the right for a quick explanation of the different types of sender addresses in e-mails.

Source: [Sender Policy Framework](http://www.openspf.org/Introduction), retreived 16.03.2016

<br>
## Installation

This script depends on [php](http://php.net/) and [rephluX](https://github.com/rephluX)'s [spf-resolver](https://github.com/rephluX/spf-resolver) (and thus on [composer](https://getcomposer.org/download/)).

##### 1. Check out this repo to a folder on your system

##### 2. Get Composer

Run this in your terminal to get the latest Composer version:

(**check [composer](https://getcomposer.org/download/) for current instructions!)** and https://getcomposer.org/ for more information.)

```
php -r "readfile('https://getcomposer.org/installer');" > composer-setup.php
php -r "if (hash('SHA384', file_get_contents('composer-setup.php')) === '41e71d86b40f28e771d4bb662b997f79625196afcca95a5abf44391188c695c6c1456e16154c75a211d238cc3bc5cb47') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

##### 3. Get spf-resolver

Run the following command in your terminal to install spf-resolver:

```
php composer.phar install
```

##### 4. Make the folder available on a php-enabled webserver.


<br>
## Usage

### URL

`http://your.server/path/to/mailhost-resolver/index.php&domain={domainname}&IPv=[4|6]&type=[mx|spf]`

(values in brackets `[]` are alternatives! See below.)

If your server is configured to run `index.php` as the default file name for a directory, you can shorten it to:

`http://your.server/path/to/mailhost-resolver/&domain={domainname}&IPv=[4|6]&type=[mx|spf]`

### Parameters

The script supports the following parameters (case sensitive):

#### `domain={domainname}` (mandatory)

Should accept any valid domain name (currently limited to 300 chars, see source). To support [Internationalized domain names](https://en.wikipedia.org/wiki/Internationalized_domain_name), the [intl php extension](http://php.net/manual/en/intl.installation.php) needs to be installed and enabled.

#### `IPv=[4|6]` (optional)

Limits the result to addresses from the given IP address version, or all if not given.

#### `type=[mx|spf]`  (optional)

Limits the result to addresses from the given DNS ressource records, or all if not given.


<br>
---
$Revision: 3458 $
