<?php

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Payment.php';

use PHPUnit\Framework\TestCase;
use CodeAndGraphics\WalletOne\Payment;

class SignatureTest extends TestCase
{
	public function testBuildSignature() {
		$payment = new Payment(100000000000, '52er5ReD3UZUjU7r?HefrafreGUGagat');
		$payment
			->setAmount(100)
			->setCurrencyId(643)
			->setPaymentId('20100-11')
			->setDescription('Payment for order #20100-11 in code.and.graphics')
			->setExpiredDate('2018-12-31T23:59:59')
			->setSuccessUrl('https://code.and.graphics/w1/success')
			->setFailUrl('https://code.and.graphics/w1/fail')
			->setCustomParameters([
				'CustomParam1' => 'Value1',
				'CustomParam2' => 'Value2',
				'CustomParam3' => 'Value3'
			]);

		$signature = $payment->getSignature();

		// Signature calculated from example: https://www.walletone.com/en/merchant/documentation/
		$this->assertEquals($signature, 'Zgz9vGUol2/Zhqo5bcsptA==');
	}
}