<?php
/**
 *
 * This source file is subject to the Ecommerce Shift Software License, which is available at http://ecommerceshift.com/common/license-commercial-ru.txt.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 *
 * NOTICE OF LICENSE
 *
 * You may not sell, sub-license, rent or lease
 * any portion of the Software or Documentation to anyone.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer
 * versions in the future.
 *
 * @copyright  Copyright (c) 2013 Ecommerce Shift (http://ecommerceshift.com/)
 * @contacts   support@ecommerceshift.com
 * @author     Alexander Dashkov (dashkov1@gmail.com)
 * @license    http://ecommerceshift.com/common/license-commercial-ru.txt
 */
class Eshift_Smssend_Model_Smssend extends Varien_Object
{
    protected $_smsGateway;
    protected $_smsLogin;
    protected $_smsPass;
    protected $_smsApiKey;
    protected $_smsSender;
    protected $_globalEnable;
    protected $_logEnabled;
    protected $_smsText;
    protected $_smsReceiver;
    protected $_helper;

    const XML_PATH_SMS_GLOBAL_ENABLE = 'smssend/smssend/sms_enable';
    const XML_PATH_SMS_GATEWAY = 'smssend/smssend/sms_gateway';
    const XML_PATH_SMS_LOGIN = 'smssend/smssend/sms_login';
    const XML_PATH_SMS_PASS = 'smssend/smssend/sms_pass';
    const XML_PATH_SMS_API_KEY = 'smssend/smssend/sms_api_key';
    const XML_PATH_SMS_SENDER = 'smssend/smssend/sms_sender';
    const XML_PATH_SMS_LOG_ENABLED = 'smssend/smssend/sms_log_enable';

    protected function _construct()
    {
        $this->setGlobalEnable(Mage::getStoreConfigFlag(self::XML_PATH_SMS_GLOBAL_ENABLE));
        $this->setSmsGateway(Mage::getStoreConfig(self::XML_PATH_SMS_GATEWAY));
        $this->setSmsApiKey(Mage::getStoreConfig(self::XML_PATH_SMS_API_KEY));
        $this->setSmsSender(Mage::getStoreConfig(self::XML_PATH_SMS_SENDER));
        $this->setLogEnabled(Mage::getStoreConfigFlag(self::XML_PATH_SMS_LOG_ENABLED));
        $this->setSmsLogin(Mage::getStoreConfig(self::XML_PATH_SMS_LOGIN));
        $this->setSmsPass(Mage::getStoreConfig(self::XML_PATH_SMS_PASS));
        $this->_helper = Mage::helper('smssend');
    }

    public function sendSms()
    {
        if ($this->_globalEnable && Mage::helper('escommon')->isUserEmailSet()) {
            try {
                if (empty($this->_smsGateway)) {
                    throw new Exception('Gateway not set.');
                }
                if (empty($this->_smsApiKey)) {
                    throw new Exception('Api Key not set.');
                }
                if (empty($this->_smsText)) {
                    throw new Exception('SMS text is not set.');
                }

                if (empty($this->_smsReceiver)) {
                    throw new Exception('SMS receiver is not set.');
                }

                $sms_data = array(
                    'smstext' => $this->_smsText,
                    'sms_to' => $this->_smsReceiver,
                    'sms_login' => $this->_smsLogin,
                    'sms_api_key' => $this->_smsApiKey,
                    'sms_from' => $this->_smsSender,
                    'sms_gateway' => $this->_smsGateway
                );

                $reply = $this->_sendRequest();

                if (empty($reply)) {
                    throw new Exception('SMS Gateway not responded to your request.');
                } else {
                    $this->_log($this->_helper->__('Received response from SMS Gateway.'));
                    $this->_log($reply);
                    $this->_log($sms_data);
                }

            } catch (Exception $e) {
                $this->_log($this->_helper->__('Problem with sending SMS: ').$e->getMessage());
                return;
            }

        } else {
            $this->_log($this->_helper->__('SMS is disabled globally.'));
        }

    }
    /**
     * @param mixed $globalEnable
     */
    public function setGlobalEnable($globalEnable)
    {
        $this->_globalEnable = $globalEnable;
    }

    /**
     * @param mixed $logEnabled
     */
    public function setLogEnabled($logEnabled)
    {
        $this->_logEnabled = $logEnabled;
    }

    /**
     * @param mixed $smsApiKey
     */
    public function setSmsApiKey($smsApiKey)
    {
        $this->_smsApiKey = $smsApiKey;
    }

    /**
     * @param mixed $smsGateway
     */
    public function setSmsGateway($smsGateway)
    {
        $this->_smsGateway = $smsGateway;
    }

    /**
     * @param mixed $smsSender
     */
    public function setSmsSender($smsSender)
    {
        $this->_smsSender = $smsSender;
    }

    /**
     * @param mixed $smsReceiver
     */
    public function setSmsReceiver($smsReceiver)
    {
        $this->_smsReceiver = $smsReceiver;
    }

    /**
     * @param mixed $smsText
     */
    public function setSmsText($smsText)
    {
        $this->_smsText = urlencode($smsText);
    }

    /**
     * @param mixed $smsLogin
     */
    public function setSmsLogin($smsLogin)
    {
        $this->_smsLogin = $smsLogin;
    }

    /**
     * @param mixed $smsPass
     */
    public function setSmsPass($smsPass)
    {
        $this->_smsPass = $smsPass;
    }


    private function _log($message)
    {
        if ($this->_logEnabled) {
            Mage::log($message, null, 'smssend.log');
        }
    }

    private function _sendRequest() {

        $curl = new Varien_Http_Adapter_Curl();

        $curl->setConfig(array(
            'timeout'   => 15
        ));

        switch ($this->_smsGateway){

            case 'smspilot':
                $from = ($this->_smsSender ? '&from='.$this->_smsSender : '');
                $url = 'http://smspilot.ru/api.php?send='.$this->_smsText.'&to='.$this->_smsReceiver.'&apikey='.$this->_smsApiKey.$from.'';
                $curl->write(Zend_Http_Client::GET, $url);
                $reply = $curl->read();

                break;

            case 'smsru':
                $from = ($this->_smsSender ? '&from='.$this->_smsSender : '');
                $url = 'http://sms.ru/sms/send?text='.$this->_smsText.'&to='.$this->_smsReceiver.'&api_id='.$this->_smsApiKey.$from.'';
                $curl->write(Zend_Http_Client::GET, $url);
                $reply = $curl->read();

                break;

            case 'unisender':
                if (empty($this->_smsSender)) {
                    throw new Exception('Sender is not set.');
                }
                $from = ($this->_smsSender ? '&sender='.$this->_smsSender : '');
                $url = 'http://api.unisender.com/ru/api/sendSms?format=json&text='.$this->_smsText.'&phone='.$this->_smsReceiver.'&api_key='.$this->_smsApiKey.$from.'';
                $curl->write(Zend_Http_Client::GET, $url);
                $reply = $curl->read();

                break;

            case 'bytehand':
                if (empty($this->_smsLogin)) {
                    throw new Exception('Bytehand ID is not set.');
                }
                if (empty($this->_smsSender)) {
                    throw new Exception('Sender is not set.');
                }
                $from = ($this->_smsSender ? '&from='.$this->_smsSender : '');
                $url = 'http://bytehand.com:3800/send?id='.$this->_smsLogin.'&text='.$this->_smsText.'&to='.$this->_smsReceiver.'&key='.$this->_smsApiKey.$from.'';
                $curl->write(Zend_Http_Client::GET, $url);
                $reply = $curl->read();

                break;

            case 'log':
                $reply = 'SMS Sent';
                break;

            default:
                $reply = 'SMS provider not set.';
        }

        return $reply;
    }

}
