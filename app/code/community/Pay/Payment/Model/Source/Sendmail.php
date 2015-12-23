<?php

class Pay_Payment_Model_Source_Sendmail {
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {

        return array(
            array('value' => 'start', 'label' => 'Bij starten betaling'),
            array('value' => 'success', 'label' => 'Na succesvolle betaling'),            
        );
    }

}
