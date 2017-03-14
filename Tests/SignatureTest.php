<?php

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Payment.php';

use PHPUnit\Framework\TestCase;
use CodeAndGraphics\WalletOne\Payment;

class SignatureTest extends TestCase
{
	public function testBuildSignature() {
		$payment = new Payment(119175088534, 'XkZMYW56NzVbNV1aekxGNVxvT3xwVHExZ005');
		$payment
			->setAmount(100)
			->setCurrencyId(643)
			->setPaymentId('12345-001')
			->setDescription('Payment for order #12345-001 in MYSHOP.com')
			->setExpiredDate('2019-12-31T23:59:59')
			->setSuccessUrl('https://myshop.com/w1/success.php')
			->setFailUrl('https://myshop.com/w1/fail.php')
			->setCustomParameters([
				'MyShopParam1' => 'Value1',
				'MyShopParam2' => 'Value2',
				'MyShopParam3' => 'Value3'
			]);

		$signature = $payment->getSignature();

		// Signature calculated from example: https://www.walletone.com/en/merchant/documentation/
		$this->assertEquals($signature, '7qnRb5mi+2viZbYS3wKlhQ==');
	}

	public function testResponseSignature() {

		$post = array (
			'WMI_AUTO_ACCEPT' => '1',
			'WMI_COMMISSION_AMOUNT' => '0.03',
			'WMI_CREATE_DATE' => '2017-03-14 19:48:45',
			'WMI_CURRENCY_ID' => '643',
			'WMI_DESCRIPTION' => 'Payment for order #12345-001 in MYSHOP.com',
			'WMI_EXPIRED_DATE' => '2017-04-14 19:48:45',
			'WMI_FAIL_URL' => 'https://myshop.com/w1/fail.php',
			'WMI_LAST_NOTIFY_DATE' => '2017-03-14 20:41:54',
			'WMI_MERCHANT_ID' => '119175088534',
			'WMI_NOTIFY_COUNT' => '12',
			'WMI_ORDER_ID' => '305421309635',
			'WMI_ORDER_STATE' => 'Accepted',
			'WMI_PAYMENT_AMOUNT' => '100.00',
			'WMI_PAYMENT_NO' => '12345-001',
			'WMI_PAYMENT_TYPE' => 'WalletOneRUB',
			'WMI_SUCCESS_URL' => 'https://myshop.com/w1/success.php',
			'WMI_TO_USER_ID' => '135723121206',
			'WMI_UPDATE_DATE' => '2017-03-14 20:00:46',
			'WMI_SIGNATURE' => 'OHPVU/Us1Rr3Pn68MDexrw==',
		);

		$payment = new Payment(119175088534,'XkZMYW56NzVbNV1aekxGNVxvT3xwVHExZ005');

		if($payment->validate($post)) {
			$response = $payment->getSuccessAnswer();
		} else {
			$response = $payment->getRetryAnswer();
		}

		$this->assertEquals('WMI_RESULT=OK', $response);
	}
}