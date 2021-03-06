<?php

namespace Services\DnsHosting;

include "../../src/php/DNSHostingAPIv1.php";
include "auth.php";

$api = new DNSHostingAPIv1(DNS_API_ENDPOINT);
$result = $api->Login(DNS_API_LOGIN, DNS_API_PASSWORD, DNS_API_RESELLER);
if ($result === false) {
    echo "Login unsuccessful";
    exit(0);
}

$result = $api->DomainZoneRecords("mfo.org.ua");
var_dump($result["total_count"]);
var_dump($result["list"]);
var_dump($api->ErrorMessage());
