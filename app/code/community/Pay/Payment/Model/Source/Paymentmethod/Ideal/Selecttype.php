<?php
class Pay_Payment_Model_Source_Paymentmethod_Ideal_Selecttype {
    
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        
        return array(
            array('value' => 'none', 'label'=>'Geen bankselectie'),
            array('value' => 'radio', 'label'=>'Radiobuttons'),
            array('value' => 'select', 'label'=>'Selectbox'),
        );
       
    }
   
}