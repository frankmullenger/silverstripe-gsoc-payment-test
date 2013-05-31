<?php

class DummyMerchantHostedTest extends SapphireTest {

	public $data;
	public $processor;

	public function setUp() {
		parent::setUp();

		$paymentMethods = array('test' => array('DummyMerchantHosted'));
		Config::inst()->remove('PaymentProcessor', 'supported_methods');
		Config::inst()->update('PaymentProcessor', 'supported_methods', $paymentMethods);

		Config::inst()->remove('PaymentGateway', 'environment');
		Config::inst()->update('PaymentGateway', 'environment', 'test');

		$this->data = array(
			'Amount' => '10',
			'Currency' => 'USD',
			'FirstName' => 'Ryan',
			'LastName' => 'Dao',
			'CreditCardType' => 'master',
			'CardNumber' => '4381258770269608',
			'MonthExpiry' => '11',
			'YearExpiry' => '2016',
			'Cvc2' => '146'
		);

		$this->processor = PaymentFactory::factory('DummyMerchantHosted');
	}

	public function testClassConfig() {
		$processor = PaymentFactory::factory('DummyMerchantHosted');
		$this->assertEquals(get_class($processor), 'DummyProcessor_MerchantHosted');
		$this->assertEquals(get_class($processor->gateway), 'DummyGateway_MerchantHosted');
		$this->assertEquals(get_class($processor->payment), 'Payment');
	}

	public function testPaymentSuccess() {
		$this->processor->capture($this->data);
		$this->assertEquals($this->processor->payment->Status, Payment::SUCCESS);
	}

	public function testConnectionError() {
		$this->data['Amount'] = '10.01';
		$this->processor->capture($this->data);
		$this->assertEquals($this->processor->payment->Status, Payment::FAILURE);
		$this->assertEquals($this->processor->payment->HTTPStatus, '500');
	}

	public function testPaymentFailure() {
		$this->data['Amount'] = '10.02';
		$this->processor->capture($this->data);
		
		$this->assertEquals($this->processor->payment->Status, Payment::FAILURE);

		$error = $this->processor->payment->Errors()->first();
		$this->assertEquals($error->ErrorMessage, 'Payment cannot be completed');
		$this->assertEquals($error->ErrorCode, '1A');
	}

	public function testPaymentIncomplete() {
		$this->data['Amount'] = '10.03';
		$this->processor->capture($this->data);
		$this->assertEquals($this->processor->payment->Status, Payment::INCOMPLETE);
		
		$error = $this->processor->payment->Errors()->first();
		$this->assertEquals($error->ErrorMessage, 'Awaiting payment confirmation');
		$this->assertEquals($error->ErrorCode, '1B');
	}
}