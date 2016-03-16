<?php
/*
 * mailhost-resolver $Revision: 3461 $
 * https://github.com/metaworx/mailhost-resolver
 *
 *
 * Copyright (c) 2016 metaworx resonare rüegg, Switzerland
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Rephlux\SpfResolver\SpfResolver;

function exception_error_handler($errno, $errstr, $errfile, $errline )
{
	if (error_reporting() === 0)
		return;

	print_error("#$errno $errstr\n$errline $errfile\n", 500);
}

set_error_handler("exception_error_handler");

ob_start();

function print_error($text, $statusCode=0)
{
	if ($statusCode)
		http_response_code($statusCode);

	else if ($statusCode && http_response_code() < 500)
		http_response_code(400);
		// 400 bad request

	echo "#\n# ERROR: ".str_replace("\n", "\n#        ", $text)."\n";
}

function print_and_die($text, $statusCode=0)
{
	print_error($text, $statusCode);
	ob_end_flush();
	die();
}


function print_ip($IPv, $ip='', $hostname='')
{

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

	if ($ip && ($IPv == 5 || (($IPv == 4) == preg_match('/^\d+\./', $ip))))
	{
		echo "$ip\n";
	}

	if ($hostname)
	{
		echo "# $hostname\n";

		if ($IPv <= 5 && $hosts = gethostbynamel($hostname))
		{
			foreach ($hosts as $ip)
			{
				echo "$ip\n";
			}
		}

		if ($IPv >= 5 && $hosts = gethostbynamel6($hostname))
		{
			foreach ($hosts as $ip)
			{
				echo "$ip\n";
			}
		}
	}
}


# http://php.net/manual/de/function.gethostbyname.php#70936
function gethostbyname6($host, $try_a = false)
{
	// get AAAA record for $host
	// if $try_a is true, if AAAA fails, it tries for A
	// the first match found is returned
	// otherwise returns false

	$dns = gethostbynamel6($host, $try_a);
	if ($dns == false)
		{ return false; }
	else
		{ return $dns[0]; }
}

function gethostbynamel6($host, $try_a = false)
{
	// get AAAA records for $host,
	// if $try_a is true, if AAAA fails, it tries for A
	// results are returned in an array of ips found matching type
	// otherwise returns false

	$dns6 = dns_get_record($host, DNS_AAAA);
	if ($try_a == true)
	{
		$dns4 = dns_get_record($host, DNS_A);
		$dns = array_merge($dns4, $dns6);
	}
	else
		{ $dns = $dns6; }

	$ip6 = array();
	$ip4 = array();

	foreach ($dns as $record)
	{
		if ($record["type"] == "A")
		{
			$ip4[] = $record["ip"];
		}
		if ($record["type"] == "AAAA")
		{
			$ip6[] = $record["ipv6"];
		}
	}
	if (count($ip6) < 1)
	{
		if ($try_a == true)
		{
			if (count($ip4) < 1)
			{
				return false;
			}
			else
			{
				return $ip4;
			}
		}
		else
		{
			return false;
		}
	}
	else
	{
		return $ip6;
	}
}


$tsstring = gmdate('D, d M Y H:i:s ') . 'GMT';


header("Last-Modified: $tsstring");
header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
header("Pragma: no-cache"); //HTTP 1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header('Content-Type:text/plain');

echo "# File generated $tsstring by\n";
echo '# mailhost-resolver $Revision: 3461 $'."\n";
echo "# https://github.com/metaworx/mailhost-resolver\n";
echo "#\n";


$maxlen=300;


if (!$intl = extension_loaded('intl'))
{
	echo "# INFO: international domain-names not supported\n#\n";
}


if (!isset($_GET['IPv']))
{
	$IPv=5;
}
else
{
	if (strlen($_GET['IPv']) > 2) print_and_die("IP version parameter exeeds 1 char");

	$IPv=$_GET['IPv'];

	if ($IPv != 4 && $IPv != 6)
		print_and_die("no valid IP version: $IPv");

}

echo "# IP version: ".($IPv==5?"any":$IPv)."\n";


if (!isset($_GET['type']))
{
	$type='';
	$doSpf=true;
	$doMx=true;
}
else
{
	$doSpf=false;
	$doMx=false;

	if (strlen($_GET['type']) > 3)
		print_and_die("type parameter too long");

	$type=$_GET['type'];

	switch ($type)
	{
		case "mx":
			$doMx=true;
			break;

		case "spf":
			$doSpf=true;
			break;

		default:
			print_and_die("unknown type $type");
			break;
	}
}

if ($doSpf)
{
	$autoload=__DIR__ . '/vendor/autoload.php';

	if (file_exists($autoload))
		@include_once($autoload);

	if (class_exists('Rephlux\SpfResolver\SpfResolver'))
	{
		$spf = new SpfResolver();
	}
	else
	{
		$spfMessage="SpfResolver not installed\ncheck out https://github.com/rephluX/spf-resolver";

		if ($type == "spf")
			print_and_die($spfMessage, 501);

		print_error($spfMessage, 501);

		$doSpf=false;
	}
}

echo "# Resource type: ".($type?$type:"any")."\n";


if (!isset($_GET['domain']))
	print_and_die("missing domain parameter");

if (strlen($_GET['domain']) > $maxlen)
	print_and_die("domain parameter exeeds $maxlen", 414);

$domains=$_GET['domain'];

# http://stackoverflow.com/a/16491074/3102305
$regex='^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$';

foreach (explode(",", $domains) as $domain)
{
	# http://stackoverflow.com/a/14333744/3102305
	if ($intl)
		$domain = idn_to_ascii($domain);

	if (!preg_match("/$regex/i", $domain))
	{

		print_error("not a valid domain name: '$domain'\n");

		continue;
	}

	echo "\n#\n# $domain\n#\n";

	if ($doSpf)
	{
		echo "\n# SPF records\n";

		if ($ipAddresses = $spf->resolveDomain($domain))
		{
			foreach ($ipAddresses as $ip)
			{
				print_ip($IPv, $ip);
			}
		}
	}


	if ($doMx)
	{
		echo "\n# MX records\n";
		if (getmxrr($domain, $mxhosts ))
		{
			foreach($mxhosts as $host)
			{
				print_ip($IPv, NULL, $host);
			}
		}
	}
}

ob_end_flush();
