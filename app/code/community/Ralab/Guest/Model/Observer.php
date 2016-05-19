<?php 
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
        

        
        if ($order->getCustomerId() === NULL) {


            $_billingAddress = $order->getBillingAddress();
            $_isGuest = $order->getCustomerIsGuest();
            $street = $_billingAddress->getStreet();

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

            //BILL

            //Build billing and shipping address for customer, for checkout 
            $_custom_address = array(
                'firstname' => $firstname,
                'lastname' => $lastname,
                'street' => array(
                    '0' => $street[0],
                    //'1' => $street[1],
                ),
                'city' => $_billingAddress->getCity(),
                'region_id' => $_billingAddress->getRegionId(),
                'region' => $_billingAddress->getRegion(),
                'postcode' => $_billingAddress->getPostcode(),
                'country_id' => $_billingAddress->getCountryId(),
                'telephone' => $_billingAddress->getTelephone(),
                'fax' => $_billingAddress->getFax(),
            );
            $customAddress = Mage::getModel('customer/address');
            $customAddress->setData($_custom_address)
                    ->setCustomerId($customer->getId())
                    ->setIsDefaultBilling('1')
                    ->setIsDefaultShipping('1')
                    ->setSaveInAddressBook('1');

            try {
                $customAddress->save();
                
                
                $order_increment_id = $order->getIncrementId() ;
                Mage::log("id => ".$customer->getId(),null,'customer.log');
                

                if($order->getCustomerIsGuest() == "1"){
                    $order->setCustomerIsGuest(0);
                }

                if($order->getCustomerGroupId() == "0"){
                    $order->setCustomerGroupId(1);
                }
                $order->save();

                $resource = Mage::getSingleton('core/resource');
                $writeConnection = $resource->getConnection('core_write');
                $table_order = $resource->getTableName('sales_flat_order');
                $table_grid = $resource->getTableName('sales_flat_order_grid');

                $query1 = "UPDATE ". $table_order ." SET customer_id = '".$customer->getId()."', customer_is_guest=0 WHERE increment_id = '". $order_increment_id ."'  AND customer_id IS NULL;";

                $query2 = "UPDATE ". $table_grid ." SET customer_id = '". $customer->getId() ."' WHERE increment_id = '". $order_increment_id. "' AND customer_id IS NULL;";

                $writeConnection->query($query1);
                $writeConnection->query($query2);

            
            } catch (Exception $e) {
                Mage::log($e->getMessage());

            }        


            
        }
    
    }
}
