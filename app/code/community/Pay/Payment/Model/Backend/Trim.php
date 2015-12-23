<?php

class Pay_Payment_Model_Backend_Trim extends Mage_Core_Model_Config_Data {

    protected function _beforeSave() {
        $value = $this->getValue();
        $value = trim($value);
        $this->setValue($value);
        return $this;
    }

}
