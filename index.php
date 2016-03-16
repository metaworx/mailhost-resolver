<?php
/*
 * mailhost-resolver
 * https://github.com/metaworx/mailhost-resolver
 *
 *
 * Copyright (c) 2016 metaworx resonare rüegg, Switzerland
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require "vendor/autoload.php";

use Rephlux\SpfResolver\SpfResolver;


function return_error($text) {
	
	http_response_code(400); # bad request
	die($text);
}


function print_ip($IPv, $ip, $hostname) {
	
	# could use something more sophisticated. like
	# http://blog.markhatton.co.uk/2011/03/15/regular-expressions-for-ip-addresses-cidr-ranges-and-hostnames/
	# http://www.phpclasses.org/browse/file/70429.html
	#
	# http://www.w3schools.com/php/filter_validate_ip.asp
	#if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
	#
	#   echo "# $ip\n";
	#
	#}
	#
	#else
	
	if ($ip && (!isset($IPv) || (($IPv == 4) == preg_match('/^\d+\./', $ip)))) {
	
		echo "$ip\n";
	
	}
	
	if ($hostname) {
	
		echo "# $hostname\n";
		
		
		if (!isset($IPv) || $IPv == 4) {
			
			$hosts = gethostbynamel($hostname);
			
			foreach ($hosts as $ip) {
				
				echo "$ip\n";
				
			}
		}
		
		if (!isset($IPv) || $IPv == 6) {
		
			$hosts = gethostbynamel6($hostname);
			
			foreach ($hosts as $ip) {
				
				echo "$ip\n";
				
			}
		}
		
	}
}


# http://php.net/manual/de/function.gethostbyname.php#70936
function gethostbyname6($host, $try_a = false) {
	// get AAAA record for $host
	// if $try_a is true, if AAAA fails, it tries for A
	// the first match found is returned
	// otherwise returns false
	
	$dns = gethostbynamel6($host, $try_a);
	if ($dns == false) { return false; }
		else { return $dns[0]; }
}
	
function gethostbynamel6($host, $try_a = false) {
	// get AAAA records for $host,
	// if $try_a is true, if AAAA fails, it tries for A
	// results are returned in an array of ips found matching type
	// otherwise returns false
	
	$dns6 = dns_get_record($host, DNS_AAAA);
	if ($try_a == true) {
		$dns4 = dns_get_record($host, DNS_A);
		$dns = array_merge($dns4, $dns6);
	}
	else { $dns = $dns6; }
	$ip6 = array();
	$ip4 = array();
	foreach ($dns as $record) {
		if ($record["type"] == "A") {
			$ip4[] = $record["ip"];
		}
		if ($record["type"] == "AAAA") {
			$ip6[] = $record["ipv6"];
		}
	}
	if (count($ip6) < 1) {
		if ($try_a == true) {
			if (count($ip4) < 1) {
				return false;
			}
			else {
				return $ip4;
			}
		}
		else {
			return false;
		}
	}
	else {
		return $ip6;
	}
}


$tsstring = gmdate('D, d M Y H:i:s ') . 'GMT';


header("Last-Modified: $tsstring");
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache"); //HTTP 1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header('Content-Type:text/plain');

echo "# $tsstring\n";

$maxlen=300;


if (!isset($_GET['domain']))
	return_error("# missing domain parameter\n");

if (strlen($_GET['domain']) > $maxlen)
	return_error("# domain parameter exeeds $maxlen\n");

$domain=$_GET['domain'];


if (extension_loaded('intl')) {
	
	# http://stackoverflow.com/a/14333744/3102305
	$domain = idn_to_ascii($domain);
	
} else {
	
	echo "# INFO: international domain-names not supported\n";
	
}


# http://stackoverflow.com/a/16491074/3102305
$regex='^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$';


if (!preg_match("/$regex/i", $domain))
	return_error("# no valid domain name supplied: '$domain'\n");


if (isset($_GET['IPv'])) {
	
	if (strlen($_GET['IPv']) > 2) return_error("# IP version parameter exeeds 1 char\n");
	
	$IPv=$_GET['IPv'];
	
	if ($IPv != 4 && $IPv != 6)
		return_error("# no valid IP version: $IPv\n");
	
}


if (!isset($_GET['type'])) {
	
	$type="";
	
} else {
	
	if (strlen($_GET['type']) > 3)
		return_error("# type parameter too long\n");
	
	$type=$_GET['type'];
	
	if ($type != "mx" && $type != "spf")
		return_error("# unknown type $type\n");
	
}


if ($type == "" || $type == "spf") {
	
	$spf = new SpfResolver();
	
	$ipAddresses = $spf->resolveDomain($domain);
	
	echo "\n# SPF records for $domain\n";
	
	foreach ($ipAddresses as $ip) {
		
		print_ip($IPv, $ip);
		
	}
	
}


if ($type == "" || $type == "mx") {
	
	echo "\n# MX records for $domain\n";
	if (getmxrr($domain, $mxhosts )) {
		
		foreach($mxhosts as $host) {
			
			print_ip($IPv, NULL, $host);
			
		}
		
	}
	
}


