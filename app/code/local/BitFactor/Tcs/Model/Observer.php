<?php

class BitFactor_Tcs_Model_Observer
{
    public function addBookingAction(Varien_Event_Observer $observer)
    {
        $customBlock = Mage::app()->getLayout()->getBlockSingleton('BitFactor_Tcs_Block_Adminhtml_Sales_Order_View_Info_Block');
        if ($customBlock->isInvoiceCreated()) {
            $block = Mage::app()->getLayout()->getBlock('sales_order_edit');
            if (!$block) {
                return $this;
            }
            $order = Mage::registry('current_order');
            $shipping = $customBlock->getSippingInfo();
            if ($order->canShip() && empty($shipping['cn'][0])) {
                //$url = Mage::helper("adminhtml")->getUrl("/", array('order_id' => $order->getId()));
                $block->addButton(
                    'tcs_booking',
                    array(
                        'label' => Mage::helper('adminhtml')->__('TCS Book Shipment'),
                        'onclick' => 'javascript:showTcsPopup()',
                        'class' => 'go'
                    )
                );
            }
            return $this;
        }
        return $this;
    }

    public function getTcsBookWidget(Varien_Event_Observer $observer){
        $block = $observer->getBlock();
        if (($block->getNameInLayout() == 'order_info') && ($child = $block->getChild('tcs.order.info.custom.block'))) {
            $transport = $observer->getTransport();
            if ($transport) {
                $html = $transport->getHtml();
                $html .= $child->toHtml();
                $transport->setHtml($html);
            }
        }
    }
}