<?php
/**
 * Ralab Register Config
 *
 * @category    Ralab
 * @package     Ralab_Guest
 * @author      Kalpesh Balar <kalpesh.balar@gmail.com>
 * @copyright   Ralab (http://ralab.in)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Ralab_Guest_Model_Config
{
	const XML_PATH_ENABLED = 'customer/guestregister/enabled';
	
    public function isEnabled($storeId=null)
    {
		if( Mage::getStoreConfigFlag(self::XML_PATH_ENABLED, $storeId)) 
		{
        	return true;
        }
        
        return false;
    }
}
