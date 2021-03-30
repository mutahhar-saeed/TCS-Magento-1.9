<?php

class BitFactor_Tcs_Adminhtml_Sales_Order_IndexController extends Mage_Adminhtml_Controller_Action
{

    public function addAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        Mage::Register('iOrderId', $orderId);
        $this->loadLayout();
        $this->renderLayout();
    }

    public function _isAllowed()
    {
        return true;
    }

    public function markShipment($data)
    {

        $order = Mage::getModel('sales/order')->load($data['order_id']);
        if ($order->canShip()) {
            $itemQty = $order->getItemsCollection()->count();
            $ship = Mage::getModel('sales/service_order', $order)->prepareShipment($itemQty);
            $ship = new Mage_Sales_Model_Order_Shipment_Api();
            $shipmentId = $ship->create($order->getIncrementId());
        }
        $shipment_collection = Mage::getResourceModel('sales/order_shipment_collection');
        $shipment_collection->addAttributeToFilter('order_id', $data['order_id']);

        foreach ($shipment_collection as $sc) {
            $shipment = Mage::getModel('sales/order_shipment');
            $shipment->load($sc->getId());

            if ($shipment->getId() != '') {
                $track = Mage::getModel('sales/order_shipment_track')
                    ->setShipment($shipment)
                    ->setData('title', $data['carrier_title'])
                    ->setData('number', $data['consignee_number'])
                    ->setData('carrier_code', $data['carrier_code'])
                    ->setData('order_id', $shipment->getData('order_id'))
                    ->save();
            }
        }
        $this->_updateOrderStatus($order);
        return true;
    }

    function _updateOrderStatus(Mage_Sales_Model_Order $order)
    {
        $orderStatus = Mage::getStoreConfig('bitfactor_tcs_section/tcs_order_group/shipment');
        $orderStatus = (isset($orderStatus)) ? $orderStatus : 'processing';
//        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING,$orderStatus,'',false);
        $order->setData('status', $orderStatus);
        $order->save();
        return $this;
    }

    public function submitAction()
    {
        $shipmentArray = $this->getRequest()->getParams();
        $shipmentArray['username'] = Mage::getStoreConfig('bitfactor_tcs_section/bitfactor_tcs_group/api_username');
        $shipmentArray['password'] = Mage::getStoreConfig('bitfactor_tcs_section/bitfactor_tcs_group/api_password');
        $shipmentArray['costCenterCode'] = Mage::getStoreConfig('bitfactor_tcs_section/bitfactor_tcs_group/account_id');
        $clientId = Mage::getStoreConfig('bitfactor_tcs_section/bitfactor_tcs_group/bitfactor_tcs_client_id');
        $url = 'https://apis.tcscourier.com/production/v1/cod/create-order ';
        $orderData = [
            'userName' => $shipmentArray['username'],
            'password' => $shipmentArray['password'],
            'costCenterCode' => $shipmentArray['costCenterCode'],
            'consigneeName' => $shipmentArray['consigneeName'],
            'consigneeAddress' => $shipmentArray['consigneeAddress'],
            'consigneeMobNo' => $shipmentArray['consigneeMobNo'],
            'consigneeEmail' => $shipmentArray['consigneeEmail'],
            'originCityName' => $shipmentArray['originCityName'],
            'destinationCityName' => $shipmentArray['destinationCityName'],
            'weight' => $shipmentArray['weight'],
            'pieces' => $shipmentArray['pieces'],
            'codAmount' => $shipmentArray['codAmount'],
            'customerReferenceNo' => $shipmentArray['custRefNo'],
            'services' => $shipmentArray['Service'],
            'productDetails' => $shipmentArray['productDetails'],
            'fragile' => $shipmentArray['fragile'],
            'remarks' => $shipmentArray['Remarks'],
            'insuranceValue' => $shipmentArray['InsuranceValue'],
        ];
        $headers = array("accept: application/json", "x-ibm-client-id:" . $clientId);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $resCurl = curl_exec($ch);
        Mage::log('Response:' . $resCurl, null, 'TcsLog.log', true);
        $resCurl = json_decode($resCurl);

        if (isset($resCurl->returnStatus->status) && strtolower($resCurl->returnStatus->status) == 'success') {
            $message = explode(":", $resCurl->bookingReply->result);
            $cnNo = trim($message[1]);
            $dataTrack['carrier_title'] = 'TCS';
            $dataTrack['consignee_number'] = $cnNo;
            $dataTrack['carrier_code'] = 'tcs';
            $dataTrack['order_id'] = $shipmentArray['order_id'];
            if ($this->markShipment($dataTrack)) {
                Mage::getSingleton("adminhtml/session")->addSuccess("Shipment created with orderReferenceId " . $shipmentArray['custRefNo']);
                $this->_redirect("*/*/add", array("order_id" => $shipmentArray['order_id']));
                return;
            }
            Mage::getSingleton("adminhtml/session")->addSuccess("Shipment created with orderReferenceId " . $shipmentArray['custRefNo']);
            $this->_redirect("*/*/add", array("order_id" => $shipmentArray['order_id']));
            return;
        } else {
            Mage::getSingleton("adminhtml/session")->addError($resCurl->returnStatus->message . ". Please contact with TCS Support");
            $this->_redirect("*/*/add", array("order_id" => $shipmentArray['order_id']));
        }
    }
}