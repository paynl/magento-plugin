<?php

class Pay_Payment_Model_Source_Language {
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {

        return array(
            array('value' => 'nl', 'label' => 'Nederlands'),
            array('value' => 'en', 'label' => 'Engels'),
            array('value' => 'de', 'label' => 'Duits'),
            array('value' => 'es', 'label' => 'Spaans'),
            array('value' => 'it', 'label' => 'Italiaans'),
            array('value' => 'fr', 'label' => 'Frans'),
            array('value' => 'browser', 'label' => 'Browser taal gebruiken'),            
        );
    }

}
