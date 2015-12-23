<?php

class Pay_Payment_Model_Source_Iconsize {
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {

        return array(
            array('value' => '20', 'label' => '20x20px'),       
            array('value' => '25', 'label' => '25x25px'),       
            array('value' => '50', 'label' => '50x50px'),       
            array('value' => '75', 'label' => '75x75px'),       
            array('value' => '100', 'label' => '100x100px'),       
            array('value' => '50x32', 'label' => '50x32px'),
        );
    }

}
