<?php

class Pay_Payment_Model_Source_Showfee {
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {

        return array(
            array('value' => '0', 'label' => 'Nee'),
            array('value' => '1', 'label' => 'Alleen als er kosten zijn'),
            array('value' => '2', 'label' => 'Altijd'),
        );
    }

}
