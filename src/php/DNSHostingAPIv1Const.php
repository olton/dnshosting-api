<?php


class DNSHostingAPIv1Const {
    const COMMAND_LOGIN = "auth";
    const COMMAND_ZONE_TEMPLATE = "service/dns/resellers/:reseller/zone_tpl";
    const COMMAND_USER_RESOURCE = "service/dns/resellers/:reseller/users/:login";
    const COMMAND_DOMAIN_LIST = "service/dns/domains";
    const COMMAND_DOMAIN = "service/dns/domains/:domain";
    const COMMAND_DOMAIN_USER_LIST = "service/dns/domains/:domain/users";
    const COMMAND_DOMAIN_ZONE = "service/dns/domains/:domain/zone";
    const COMMAND_DOMAIN_ZONE_TEXT = "service/dns/domains/:domain/zone/txt";
    const COMMAND_DOMAIN_ZONE_HISTORY = "service/dns/domains/:domain/zone/history";
    const COMMAND_DOMAIN_ZONE_RECORDS = "service/dns/domains/:domain/zone/records";
    const COMMAND_DOMAIN_ZONE_RECORDS_BY_SUBDOMAIN = "service/dns/domains/:domain/zone/records_by_subdomain/:subdomain";
    const COMMAND_DOMAIN_ZONE_RECORD = "service/dns/domains/:domain/zone/records/:record";
    const COMMAND_DOMAIN_ZONE_DEFAULT_RESOURCE = "service/dns/domains/:domain/zone/default";
    const COMMAND_NS_LIST = "service/dns/ns";
    const COMMAND_DOMAIN_INFO = "service/dns/domains/:domain/fullinfo";
    const COMMAND_RESELLER_LIST = "resellers";
}