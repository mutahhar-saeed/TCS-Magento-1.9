<?php

class BitFactor_Tcs_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getTcsCitiesData()
    {
        $clientId = Mage::getStoreConfig('bitfactor_tcs_section/bitfactor_tcs_group/bitfactor_tcs_client_id');
        $data = Mage::app()->loadCache('tcs_cities_list');
        if (empty($data)) {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://apis.tcscourier.com/production/v1/cod/cities",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "X-IBM-Client-Id: ".$clientId,
                    "accept: application/json"
                ],
            ]);
            $err = curl_error($curl);
            $response = curl_exec($curl);
            if (empty($err)) {
                Mage::app()->saveCache($response, 'tcs_cities_list');
                $data = $response;
            }
            curl_close($curl);
        }
        return json_decode($data)->allCities;
    }
}
	 