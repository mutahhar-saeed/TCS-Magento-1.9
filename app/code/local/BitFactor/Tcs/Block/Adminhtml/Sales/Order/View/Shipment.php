<?php

class BitFactor_Tcs_Block_Adminhtml_Sales_Order_View_Shipment extends Mage_Adminhtml_Block_Widget_Form
{
    public function getDataForShipment()
    {
        $orderId = Mage::registry('iOrderId');
        $order = Mage::getModel('sales/order')->load($orderId);
        $shipmentArray = array();
        $shipmentArray['Remarks'] = Mage::getStoreConfig('bitfactor_tcs_section/tcs_order_group/remark');
        $shipmentArray['weight'] = Mage::getStoreConfig('bitfactor_tcs_section/tcs_order_group/weight');
        $originCityName = Mage::getStoreConfig('bitfactor_tcs_section/bitfactor_tcs_group/origin_city');

        if ($order) {
            $shipmentArray['custRefNo'] = str_replace('-', '', $order->getIncrementId());
            $shipmentArray['consigneeEmail'] = $order->getCustomerEmail(); // get customer Email
            $shipmentArray['consigneeName'] = $order->getShippingAddress()->getFirstname() . ' ' . $order->getShippingAddress()->getLastname(); // get customer Name
            $shipping_address = $order->getShippingAddress(); // get customer Address
            $telephone = $shipping_address->getTelephone(); // get customer Telephone
            $shipmentArray['consigneeMobNo'] = trim($telephone); //ltrim(str_replace('+92','',$telephone),'0');
            $shippingId = $order->getShippingAddress()->getId(); // get shipping id
            $shipAddress = Mage::getModel('sales/order_address')->load($shippingId);
            $destinationCityName = $order->getShippingAddress()->getCity(); // load city
            $shipmentArray['destinationCityName'] = strtoupper($destinationCityName); // load city
            $shipmentArray['consigneeAddress'] = $shipAddress->getStreetFull(); // get customer street address
            $shipmentArray['order_id'] = $orderId;
            $shipmentArray['originCityName'] = !empty($originCityName) ? $originCityName :'Karachi';
            // if($order->getPayment()->getMethod() == "cashondelivery")
            $grantTotal = $order->getGrandTotal(); // Cod Amount
            // else
            //   $grantTotal = 0;
            $shipmentArray['codAmount'] = $grantTotal;
            //$grantTotal = $order->getGrandTotal (); // Cod Amount
            $order_qty = $order->getData('total_qty_ordered'); // prodcut Qty
            $order_qty = round($order_qty);
            $shipmentArray['pieces'] = $order_qty;
            $productDetails = '';
            foreach ($order->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }
                $productDetails .= $item->getName() . " | SKU:" . $item->getSku() . " | Qty:" . (int)$item->getQtyOrdered() . ", ";
            }
            if ((float)$order->getWeight() < 0.5) {
                $shipmentArray['weight'] = 0.5;
            } else {
                $shipmentArray['weight'] = $order->getWeight();
            }

            $shipmentArray['productDetails'] = $productDetails;
        }
        return $shipmentArray;
    }
}