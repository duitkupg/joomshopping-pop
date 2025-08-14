<?php
use Joomla\Component\Jshopping\Site\Helper\Helper;

class DuitkuNotification
{
    public $merchantCode;
    public $amount; 
    public $merchantOrderId;
    public $productDetail;
    public $additionalParam;
    public $resultCode;
    public $paymentCode;
    public $merchantUserId;
    public $reference;
    public $signature;
    public $publisherOrderId;
    public $spUserHash;
    public $settlementDate;
    public $issuerCode;

    public function __construct()
    {
        $this->merchantCode = $this->getValue('merchantCode');
        $this->amount = $this->getValue('amount');
        $this->merchantOrderId = $this->getValue('merchantOrderId');
        $this->productDetail = $this->getValue('productDetail');
        $this->additionalParam = $this->getValue('additionalParam');
        $this->resultCode = $this->getValue('resultCode');
        $this->paymentCode = $this->getValue('paymentCode');
        $this->merchantUserId = $this->getValue('merchantUserId');
        $this->reference = $this->getValue('reference');
        $this->signature = $this->getValue('signature');
        $this->publisherOrderId = $this->getValue('publisherOrderId');
        $this->spUserHash = $this->getValue('spUserHash');
        $this->settlementDate = $this->getValue('settlementDate');
        $this->issuerCode = $this->getValue('issuerCode');

        $this->validateFields();
    }

    private function getValue($key)
    {
        return $_GET[$key] ?? $_POST[$key] ?? '';
    }

    public function isSuccess()
    {
        return $this->resultCode === '00';
    }
    
    public function isPending()
    {
        return $this->resultCode === '01';
    }

    public function isFailed()
    {
        return $this->resultCode === '02';
    }

    public function getTransactionStatus()
    {
        if ($this->isSuccess()) {
            return 'success';
        } elseif ($this->isFailed()) {
            return 'failed';
        } elseif ($this->isPending()) {
            return 'pending';
        } else {
            return 'unknown';
        }
    }

    public function validateSignature($merchantCode, $apiKey)
    {
        if (empty($this->signature)) {
            return false;
        }

        $expectedSignature = md5(
            $merchantCode .
                $this->amount .
                $this->merchantOrderId .
                $apiKey
        );

        if (!hash_equals($expectedSignature, $this->signature)) {
            Helper::saveToLog("duitku.log", "WARNING: Signature validation failed");
        }
        return hash_equals($expectedSignature, $this->signature);
    }

    public function validateFields()
    {
        $required = ['resultCode', 'merchantOrderId', 'reference'];

        foreach ($required as $field) {
            if (empty($this->$field)) {
                throw new Exception("Missing required notification field: $field");
            }
        }
    }

    public function toArray()
    {
        return [
            'merchantCode' => $this->merchantCode,
            'amount' => $this->amount,
            'merchantOrderId' => $this->merchantOrderId,
            'productDetail' => $this->productDetail,
            'additionalParam' => $this->additionalParam,
            'resultCode' => $this->resultCode,
            'paymentCode' => $this->paymentCode,
            'merchantUserId' => $this->merchantUserId,
            'reference' => $this->reference,
            'signature' => $this->signature,
            'publisherOrderId' => $this->publisherOrderId,
            'spUserHash' => $this->spUserHash,
            'settlementDate' => $this->settlementDate,
            'issuerCode' => $this->issuerCode,
        ];
    }

    public function toJson()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
