<?php

class Pay_Payment_Model_Source_Gateway {
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {

        return array(
            array('value' => '0', 'label' => 'Standaard'),
            array('value' => '1', 'label' => 'Failover (enkel na akkoord PAY.)'),
        );
    }

}
