<?php
namespace CodeAndGraphics\WalletOne;

use CodeAndGraphics\WalletOne\Exception\ValidationFailedException;

class Payment {

	const DATE_FORMAT = 'Y-m-d\TH:i:s';
	const BASE64_PREFIX = 'BASE64:';

	private $submitURL = 'https://wl.walletone.com/checkout/checkout/Index';
	private $key;
	private $merchantId;

	private $amount = 0.0;
	private $commission;
	private $payerId;
	private $orderId;
	private $createDate;
	private $updateDate;

	private $currencyId = 0;
	private $paymentId = '';
	private $description = '';
	private $successUrl = '';
	private $failUrl = '';
	private $expiredDate;

	private $state = 'undefined';

	private $customParameters = [];

	public $utf8 = false;

	public function __construct($merchantId, $key = "") {
		$this->merchantId = $merchantId;
		$this->key = $key;

		$dateTime = new \DateTime();
		$this->setExpiredDateTime($dateTime->add(new \DateInterval('P30D')));
	}

	public function setAmount($amount) {
		$this->amount = sprintf('%0.2f', $amount);
		return $this;
	}

	public function getAmount() {
		return $this->amount;
	}

	public function setCurrencyId($currencyId) {
		$this->currencyId = $currencyId;
		return $this;
	}

	public function getCurrencyId() {
		return $this->currencyId;
	}

	public function setPaymentId($paymentId) {
		$this->paymentId = $paymentId;
		return $this;
	}

	public function getPaymentId() {
		return $this->paymentId;
	}

	public function setDescription($description) {
		$this->description = self::BASE64_PREFIX . base64_encode($description);
		return $this;
	}

	public function getDescription() {
		$base64 = str_replace(self::BASE64_PREFIX, '', $this->description);
		return base64_decode($base64);
	}

	public function setSuccessUrl($successUrl) {
		$this->successUrl = $successUrl;
		return $this;
	}

	public function setFailUrl($failUrl) {
		$this->failUrl = $failUrl;
		return $this;
	}

	/**
	 * @param \DateTime $dateTime
	 * @return $this
	 */
	public function setExpiredDateTime(\DateTime $dateTime) {
		$this->expiredDate = $dateTime->format(self::DATE_FORMAT);
		return $this;
	}

	public function setExpiredDate($date) {
		$this->expiredDate = $date;
		return $this;
	}

	public function getExpiredDate() {
		return $this->expiredDate;
	}

	public function getCommission() {
		return $this->commission;
	}

	public function getPayerId() {
		return $this->payerId;
	}

	public function getOrderId() {
		return $this->orderId;
	}

	public function getCreateDate() {
		return $this->createDate;
	}

	public function getUpdateDate() {
		return $this->updateDate;
	}

	public function getState() {
		return $this->state;
	}

	public function setCustomParameters($customParameters) {
		$this->customParameters = $customParameters;
	}

	/**
	 * @return array
	 */
	private function getFormPayload() {
		$parameters = [
			'WMI_MERCHANT_ID' => $this->merchantId,
			'WMI_PAYMENT_AMOUNT' => $this->amount,
			'WMI_CURRENCY_ID' => $this->currencyId,
			'WMI_PAYMENT_NO' => $this->paymentId,
			'WMI_DESCRIPTION' => $this->description,
			'WMI_EXPIRED_DATE' => $this->expiredDate,
			'WMI_SUCCESS_URL' => $this->successUrl,
			'WMI_FAIL_URL' => $this->failUrl
		];
		if($this->customParameters) {
			$parameters = array_merge($parameters, $this->customParameters);
		}
		return $parameters;
	}

	/**
	 * @param array|null $params
	 * @return string
	 */
	public function getSignature($params = null) {
		if(!$params) {
			$params = $this->getFormPayload();
		}

		ksort($params, SORT_FLAG_CASE | SORT_STRING);
		$data = [];
		foreach($params as $value) {
			$data[] = $value;
		}
		$data[] = $this->key;
		$data = implode("", $data);

		if($this->utf8) {
			$data = iconv('utf-8', 'windows-1251', $data);
		}

		return base64_encode(
			pack(
				'H*',
				md5($data)
			)
		);
	}

	/**
	 * @param array $options
	 * @return string
	 */
	public function getPaymentForm(array $options) {
		$defaultOptions = [
			'extraHTML' => '',
			'formID' => '',
			'autoSubmit' => false
		];

		$options = array_merge($defaultOptions, $options);
		if($options['autoSubmit'] && empty($options['formID'])) {
			$options['formID'] = uniqid('form_', true);
		}

		$params = $this->getFormPayload();
		if(!empty($this->key)) {
			$signature = $this->getSignature();
			$params['WMI_SIGNATURE'] = $signature;
		}

		$formHtml = [];
		if($this->utf8) {
			$formHtml[] = '<meta charset="utf-8">';
		}

		$formHtml[] = '<form id="' . $options['formID'] . '" method="post" action="' . $this->submitURL . '" '.($this->utf8 ? 'accept-charset="UTF-8"' : '').'>';
		foreach($params as $k => $v) {
			$formHtml[] = '<input type="hidden" name="'.$k.'" value="'.htmlspecialchars($v).'"/>';
		}
		$html = implode("\n", $formHtml);
		$html .= $options['extraHTML'];
		$html .= '</form>';

		if($options['autoSubmit']) {
			$html .= '<script>document.getElementById("'.addslashes($options['formID']).'").submit()</script>';
		}

		return $html;
	}

	/**
	 * @param array $data
	 * @throws ValidationFailedException
	 */
	public function validate($data) {
		if(empty($data['WMI_MERCHANT_ID'])) {
			throw new ValidationFailedException('WMI_MERCHANT_ID is not specified');
		}
		if((int) $this->merchantId !== (int) $data['WMI_MERCHANT_ID']) {
			throw new ValidationFailedException('Merchants don\'t match');
		}
		if(empty($data['WMI_SIGNATURE'])) {
			throw new ValidationFailedException('WMI_SIGNATURE is not specified');
		}
		$origSignature = $data['WMI_SIGNATURE'];
		unset($data['WMI_SIGNATURE']);

		$calculatedSignature = $this->getSignature($data);

		if($calculatedSignature !== $origSignature) {
			throw new ValidationFailedException('Signatures don\'t match');
		}

		$fieldsMap = [
			'WMI_PAYMENT_AMOUNT'    => 'amount',
			'WMI_COMMISSION_AMOUNT' => 'commission',
			'WMI_CURRENCY_ID'       => 'currency',
			'WMI_TO_USER_ID'        => 'payerId',
			'WMI_PAYMENT_NO'        => 'paymentId',
			'WMI_ORDER_ID'          => 'orderId',
			'WMI_DESCRIPTION'       => 'description',
			'WMI_SUCCESS_URL'       => 'successUrl',
			'WMI_FAIL_URL'          => 'failUrl',
			'WMI_EXPIRED_DATE'      => 'expiredDate',
			'WMI_CREATE_DATE'       => 'createDate',
			'WMI_UPDATE_DATE'       => 'updateDate',
			'WMI_ORDER_STATE'       => 'state'
		];

		$this->customParameters = [];

		foreach($data as $k => $v) {
			if(isset($fieldsMap[$k])) {
				$key = $fieldsMap[$k];
				$this->{$key} = $v;
			}
			elseif(stripos($k, 'WMI_') === false) {
				$this->customParameters[$k] = $v;
			}
		}
	}

	/**
	 * @param string $message
	 * @return string
	 */
	public function getSuccessAnswer($message = "") {
		$res = 'WMI_RESULT=OK';
		if($message) {
			$res .= '&WMI_DESCRIPTION='.urlencode($message);
		}
		return $res;
	}
}
