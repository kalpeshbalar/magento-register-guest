<?php 
/**
 * Ralab Register Observer
 *
 * @category    Ralab
 * @package     Ralab_Guest
 * @author      Kalpesh Balar <kalpesh.balar@gmail.com>
 * @copyright   Ralab (http://ralab.in)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Ralab_Guest_Model_Observer
{
    public function registerGuestUser($observer)
    {
        if (!Mage::getSingleton('guest/config')->isEnabled()) {
            return;
        }

        $order = $observer->getEvent()->getOrder(); 	

        $autoregister_array["customerSaved"] = false;
        $email = $order->getCustomerEmail();
        $firstname  = $order->getCustomerFirstname();
        $lastname  = $order->getCustomerLastname();

        $customer = Mage::getModel('customer/customer');

        $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email);

        if (!$customer->getId()) {

            $randomPassword = $customer->generatePassword(12);
            $customer->setId(null)
                ->setSkipConfirmationIfEmail($email)
                ->setFirstname($firstname)
                ->setLastname($lastname)
                ->setEmail($email)
                ->setPassword($randomPassword)
                ->setPasswordConfirmation($randomPassword);

            $errors = array();
            $validationCustomer = $customer->validate();

            if (is_array($validationCustomer)) {
                $errors = array_merge($validationCustomer, $errors);
            }

            $validationResult = count($errors) == 0;

            if (true === $validationResult) {
                $customer->save();
                $autoregister_array["customerSaved"] = true;
                $customer->sendNewAccountEmail();
            } else {
                Mage::log($errors);
            }
        }
    }
}
