<?php
namespace ZipMoney\ZipMoneyPayment\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ClientException;

use \zipMoney\Configuration;
/**
 * Class TransactionCapture
 */
class TransactionCancel extends AbstractTransaction implements ClientInterface
{   
    protected $_service = null;
    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \ZipMoney\ZipMoneyPayment\Helper\Payload $payloadHelper,
        \ZipMoney\ZipMoneyPayment\Helper\Logger $logger,   
        \ZipMoney\ZipMoneyPayment\Helper\Data $helper,
        \ZipMoney\ZipMoneyPayment\Model\Config $config,
        \zipMoney\Api\ChargesApi $chargesApi,
        array $data = []
    ) {
       
       parent::__construct($context,$encryptor,$payloadHelper,$logger,$helper,$config);

       $this->_service = $chargesApi;
    }

    /**
     * @param \Magento\Payment\Gateway\Http\TransferInterface $transferObject
     * @return null
     */
    public function placeRequest(\Magento\Payment\Gateway\Http\TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();
        $zipmoney_charge_id = $request['zipmoney_checkout_id'];
        
        $response = null;

        try {
            $cancel = $this->_service->chargesCancel($zipmoney_charge_id, $this->_helper->generateIdempotencyKey());
            $response =  ["api_response" => $cancel];
            $this->_logger->debug("Cancel Response:- ".$this->_helper->json_encode($cancel));

        } catch(\zipMoney\ApiException $e){
            $this->_logger->debug("Error:-".$e->getCode().$e->getMessage()."-".json_encode($e->getResponseBody()));
            $message = $this->_helper->__("Could not process the payment");

            if($e->getCode() == 402 && 
                $mapped_error_code = $this->_config->getMappedErrorCode($e->getResponseObject()->getError()->getCode())){
                $message = $this->_helper->__('The payment was declined by Zip.(%s)',$mapped_error_code);
            }
            $response['message'] = $message;
        }   finally {
            $log['response'] = $response;
        }

        return $response;
    }


}