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
}