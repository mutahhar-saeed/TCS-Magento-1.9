<?php

class BitFactor_Tcs_Block_Adminhtml_Sales_Order_View_Info_Block extends Mage_Core_Block_Template
{
    protected $order;

    public function getOrder()
    {
        if (is_null($this->order)) {
            if (Mage::registry('current_order')) {
                $order = Mage::registry('current_order');
            } elseif (Mage::registry('order')) {
                $order = Mage::registry('order');
            } else {
                $order = new Varien_Object();
            }
            $this->order = $order;
        }
        return $this->order;
    }

    public function getSippingInfo()
    {
        $entityId = $this->getOrder()->getEntityId();
        $data = array();
        if ($entityId) {
            $data['country'] = $this->getOrder()->getShippingAddress()->getCountry();
            foreach ($this->getOrder()->getTracksCollection() as $_track) {
                //var_dump($_track->getData('title'));
                //var_dump($_track->getData('carrier_code'));
                $data['cn'][] = $_track->getNumber();
                $data['tracking_data'] = $_track->getData();
            }
        }
        return $data;
    }

    public function isInvoiceCreated()
    {
        $invoiceCreated = false;
        foreach ($this->getOrder()->getInvoiceCollection() as $row) {
            if ($row->getState() != 3) {
                $invoiceCreated = true;
                break;
            }
        }
        return $invoiceCreated;
    }
}