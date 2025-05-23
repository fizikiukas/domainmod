<?php
/**
 * /classes/DomainMOD/Hostinger.php
 *
 * This file is part of DomainMOD, an open source domain and internet asset manager.
 * Copyright (c) 2010-2025 Greg Chetcuti <greg@greg.ca>
 *
 * Project: http://domainmod.org   Author: https://greg.ca
 *
 * DomainMOD is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * DomainMOD is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with DomainMOD. If not, see
 * http://www.gnu.org/licenses/.
 *
 */
//@formatter:off
namespace DomainMOD;

class Hostinger
{
    public $format;
    public $log;

    public function __construct()
    {
        $this->format = new Format();
        $this->log = new Log('class.hostinger');
    }

    public function getApiUrl($command, $domain)
    {
        $base_url = 'https://developers.hostinger.com/api/domains/v1/';

        if ($command == 'domainlist') {
            return $base_url . 'portfolio'; 
        } elseif ($command == 'info') {
            return $base_url . 'portfolio/' . $domain;
        } else {
            return 'Unable to build API URL';
        }
    }

    public function apiCall($full_url, $api_key)
    {
        $handle = curl_init($full_url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $api_key,
            'Accept: application/json'));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($handle);
        curl_close($handle);

        return $result;
    }

    public function getDomainList($api_key)
    {
        $domain_list = array();
        $domain_count = 0;

        $api_url = $this->getApiUrl('domainlist', '');
        $api_results = $this->apiCall($api_url, $api_key);
        $array_results = $this->convertToArray($api_results);

        if (isset($array_results)) {

            $domain_list = array();
            $domain_count = 0;

            foreach ($array_results as $domain) {

                    $domain_list[] = $domain['domain'];
                    $domain_count++;
            
            }
        } else {
            $log_message = 'Unable to get domain list';
            $log_extra = array('API Token' => $this->format->obfusc($api_key));
            $this->log->error($log_message, $log_extra);
        }

        return array($domain_count, $domain_list);
    }

    public function getFullInfo($api_key, $domain)
    {
        $expiration_date = '';
        $dns_servers = array();
        $privacy_status = '';
        $autorenewal_status = '0';

        $api_url = $this->getApiUrl('info', $domain);
        $api_results = $this->apiCall($api_url, $api_key);
        $array_results = $this->convertToArray($api_results);

        if (isset($array_results)) {
            $expiration_date = isset($array_results['expires_at']) ? substr($array_results['expires_at'], 0, 10) : '';
            $dns_servers = isset($array_results['name_servers']) ? $this->processDns($array_results['name_servers']) : array();
            $privacy_status = isset($array_results['is_privacy_protected']) ? $this->processPrivacy($array_results['is_privacy_protected']) : '';
        } else {
            $log_message = 'Unable to get domain details';
            $log_extra = array('Domain' => $domain, 'API Token' => $this->format->obfusc($api_key));
            $this->log->error($log_message, $log_extra);
        }

        return array($expiration_date, $dns_servers, $privacy_status, $autorenewal_status);
    }

    public function convertToArray($api_result)
    {
        return json_decode($api_result, true);
    }

    public function processDns($dns_result)
    {
        $dns_servers = array();
        if (!empty($dns_result)) {
            $dns_servers = array_filter($dns_result);
        } else {
            $dns_servers[0] = 'no.dns-servers.1';
            $dns_servers[1] = 'no.dns-servers.2';
        }
        return $dns_servers;
    }

    public function processPrivacy($privacy_result)
    {
        return ($privacy_result) ? '1' : '0';
    }

    public function processAutorenew($autorenewal_result)
    {
        return ($autorenewal_result) ? '1' : '0';
    }
} //@formatter:on
