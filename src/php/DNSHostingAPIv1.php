<?php

namespace Services\DnsHosting;

require_once "DNSHostingAPIv1Const.php";

use Exception;

class DNSHostingAPIv1 {
    private $_version = "1.0.0";

    private $end_point = "";

    private $_curl_present = false;
    private $_curl_info = null;
    private $_curl_raw_result = null;
    private $_curl_error = null;

    private $_tr_prefix = "";
    private $_tr_suffix = "";

    private $_auth_token = "";

    private $_command = "";
    private $_command_array = [];

    private $_user = "";
    private $_password = "";

    private $error_api= null;
    private $error = null;
    private $error_message = "";
    private $errors = [];
    private $result = [];

    /**
     * ImenaAPIv2 constructor.
     * @param string $endPoint
     * @param string $tr_prefix
     * @param string $tr_suffix
     * @throws Exception
     */
    public function __construct($endPoint = "", $tr_prefix = "API-", $tr_suffix = "-DNS-v1") {
        $this->end_point = $endPoint;
        $this->_tr_prefix = $tr_prefix;
        $this->_tr_suffix = $tr_suffix;
        $this->_curl_present = function_exists("curl_exec") && is_callable("curl_exec");
        if (!$this->_curl_present) throw new Exception("CURL required!");
    }

    /**
     * Setup endpoint
     * @param $endPoint
     */
    public function SetEndPoint($endPoint = ""){
        $this->end_point = $endPoint;
    }

    /**
     * Generate transaction ID
     * @return string
     */
    private function _transactionId(){
        return $this->_tr_prefix
            . date('YmdHis')
            .""
            . round(microtime(true),0)
            . $this->_tr_suffix;
    }

    /**
     * Execute API command with CURL lib
     * @param $method
     * @param $cmd
     * @param array $data
     * @return array|bool|mixed
     */
    private function _curlExec($method = "POST", $cmd = "", $data = []){
        $end_point = $this->end_point . $cmd;
        $headers = [
            'Content-Type: application/json',
            'Authorization: APIToken ' . $this->_auth_token,
            'Transaction: ' . $this->_transactionId(),
        ];

        $this->_command = json_encode($data);
        $this->_command_array = $data;

        $_method = strtoupper($method);

        if ($_method === 'PUT' || $_method === 'DELETE') {
            array_push($headers, "Content-Length: " . strlen($this->_command));
        }

        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $end_point);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            if ($_method !== 'GET') {
                switch ($_method) {
                    case 'POST': curl_setopt($ch, CURLOPT_POST, true); break;
                    case 'PUT':
                    case 'DELETE': curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_method); break;
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_command);
            } else {
                curl_setopt($ch, CURLOPT_HTTPGET, true);
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $this->_curl_raw_result = curl_exec($ch);
            $this->_curl_info = curl_getinfo($ch);

            curl_close($ch);

            $this->result = json_decode($this->_curl_raw_result, true);

            if (isset($this->result["message"])) {
                $this->error_api = $this->result;
                $this->error = $this->result["code"];
                $this->error_message = $this->result["message"];
                $this->errors = [$this->result];
            }

            return $this->result;
        } catch (Exception $e) {
            $this->_curl_error = $e->getMessage();
            return false;
        }
    }

    /**
     * Execute API method
     * @param $method
     * @param $command
     * @param array $arguments
     * @return bool|mixed
     */
    private function _execute($method, $command, $arguments = []){
        $result = $this->_curlExec($method, $command, $arguments);

        if (!$result) {
            return false;
        }

        if ($command === DNSHostingAPIv1Const::COMMAND_LOGIN && isset($result["token"])) {
            $this->_auth_token = $result["token"];
        }

        return isset($result["message"]) ? false : $result;
    }

    /**
     * Return class version
     * @return string
     */
    public function Ver(){
        return $this->_version;
    }

    /**
     * Return last CURL info for executed command
     * @return null
     */
    public function Info(){
        return $this->_curl_info;
    }

    /**
     * Return last raw result from CURL executing
     * @return null
     */
    public function ResultRaw(){
        return $this->_curl_raw_result;
    }

    /**
     * Return API result
     * @return array
     */
    public function Result(){
        return $this->result;
    }

    /**
     * Get last error. API or CURL
     * @param bool $api
     * @return array or string
     */
    public function Error($api = true){
        return $api ? $this->error_api : $this->_curl_error;
    }

    /**
     * Get last API error code
     * @return null
     */
    public function ErrorCode(){
        return $this->error;
    }

    /**
     * Get last API error message
     * @return string
     */
    public function ErrorMessage(){
        return $this->error_message;
    }

    /**
     * Get errors, if exist in error object
     * @return array
     */
    public function Errors(){
        return $this->errors;
    }

    /**
     * Get last API command
     * @param bool $as_array
     * @return array|string
     */
    public function Command($as_array = false){
        return $as_array ? $this->_command_array : $this->_command;
    }

    public function Login($login, $password, $reseller){
        return $this->_execute(
            "POST",
            DNSHostingAPIv1Const::COMMAND_LOGIN,
            [
                "login" => $login,
                "password" => $password,
                "reseller" => $reseller
            ]
        );
    }

    public function ZoneTemplate($reseller){
        $command = str_replace(":reseller", $reseller, DNSHostingAPIv1Const::COMMAND_ZONE_TEMPLATE);
        return $this->_execute(
            "GET",
            $command
        );
    }

    public function UserInfo($reseller, $login){
        $command = str_replace([":reseller", ":login"], [$reseller, $login], DNSHostingAPIv1Const::COMMAND_USER_RESOURCE);
        return $this->_execute(
            "GET",
            $command
        );
    }

    public function Resellers(){
        $command = DNSHostingAPIv1Const::COMMAND_RESELLER_LIST;
        return $this->_execute(
            "GET",
            $command
        );
    }

    public function Domains(){
        $command = DNSHostingAPIv1Const::COMMAND_DOMAIN_LIST;
        return $this->_execute(
            "GET",
            $command
        );
    }

    public function DomainResource($domain){
        $command = str_replace(":domain", $domain, DNSHostingAPIv1Const::COMMAND_DOMAIN);
        return $this->_execute(
            "GET",
            $command
        );
    }

    public function DomainUsers($domain){
        $command = str_replace(":domain", $domain, DNSHostingAPIv1Const::COMMAND_DOMAIN_USER_LIST);
        return $this->_execute(
            "GET",
            $command
        );
    }

    public function DomainZone($domain){
        $command = str_replace(":domain", $domain, DNSHostingAPIv1Const::COMMAND_DOMAIN_ZONE);
        return $this->_execute(
            "GET",
            $command
        );
    }

    public function DomainZoneUpdate($domain, $records){
        $command = str_replace(":domain", $domain, DNSHostingAPIv1Const::COMMAND_DOMAIN_ZONE);
        return $this->_execute(
            "PUT",
            $command,
            $records
        );
    }

    public function DomainZoneAsText($domain){
        $command = str_replace(":domain", $domain, DNSHostingAPIv1Const::COMMAND_DOMAIN_ZONE_TEXT);
        $result = $this->_execute(
            "GET",
            $command
        );
        return $result === false ? false : base64_decode($result);
    }

    public function DomainZoneRecords($domain){
        $command = str_replace(":domain", $domain, DNSHostingAPIv1Const::COMMAND_DOMAIN_ZONE_RECORDS);
        return $this->_execute(
            "GET",
            $command
        );
    }

    public function DomainZoneRecord($domain, $record_id){
        $command = str_replace([":domain", ":record"], [$domain, $record_id], DNSHostingAPIv1Const::COMMAND_DOMAIN_ZONE_RECORD);
        return $this->_execute(
            "GET",
            $command
        );
    }

    public function DomainZoneRecordsBySubdomain($domain, $subdomain){
        $command = str_replace([":domain", ":subdomain"], [$domain, $subdomain], DNSHostingAPIv1Const::COMMAND_DOMAIN_ZONE_RECORDS_BY_SUBDOMAIN);
        return $this->_execute(
            "GET",
            $command
        );
    }

    public function DomainZoneDefault($domain){
        $command = str_replace(":domain", $domain, DNSHostingAPIv1Const::COMMAND_DOMAIN_ZONE_DEFAULT_RESOURCE);
        return $this->_execute(
            "GET",
            $command
        );
    }

    public function DomainZoneHistory($domain){
        $command = str_replace(":domain", $domain, DNSHostingAPIv1Const::COMMAND_DOMAIN_ZONE_HISTORY);
        return $this->_execute(
            "GET",
            $command
        );
    }

    public function DomainInfo($domain){
        $command = str_replace(":domain", $domain, DNSHostingAPIv1Const::COMMAND_DOMAIN_INFO);
        return $this->_execute(
            "GET",
            $command
        );
    }

    public function NSList(){
        $command = DNSHostingAPIv1Const::COMMAND_NS_LIST;
        return $this->_execute(
            "GET",
            $command
        );
    }
}