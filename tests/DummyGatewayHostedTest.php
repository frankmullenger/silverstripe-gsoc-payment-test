<?php

class DummyGatewayHostedTest extends SapphireTest {

	public $data;
	public $processor;
	public $returnURL;

	public function setUp() {
		parent::setUp();

		$paymentMethods = array('test' => array('DummyGatewayHosted'));
		Config::inst()->remove('PaymentProcessor', 'supported_methods');
		Config::inst()->update('PaymentProcessor', 'supported_methods', $paymentMethods);

		Config::inst()->remove('PaymentGateway', 'environment');
		Config::inst()->update('PaymentGateway', 'environment', 'test');

		$this->data = array(
			'Amount' => '10',
			'Currency' => 'USD',
		);

		$this->returnURL = '/DummyProcessor_GatewayHosted/complete/DummyGatewayHosted';

		$this->processor = PaymentFactory::factory('DummyGatewayHosted');
	}

	public function testClassConfig() {
		$controller = PaymentFactory::factory('DummyGatewayHosted');
		$this->assertEquals(get_class($controller), 'DummyProcessor_GatewayHosted');
		$this->assertEquals(get_class($controller->gateway), 'DummyGateway_GatewayHosted');
		$this->assertEquals(get_class($controller->payment), 'Payment');
	}

	public function testConnectionError() {
		$this->data['Amount'] = 0.01;
		$result = $this->processor->capture($this->data);
		$this->assertEquals($this->processor->payment->Status, Payment::FAILURE);
		$this->assertEquals($this->processor->payment->HTTPStatus, '500');
	}

	public function testPaymentSuccess() {
		$this->processor->capture($this->data);
		$paymentID = $this->processor->payment->ID;
		$this->returnURL .= "/$paymentID";

		$query = http_build_query(array('Status' => 'Success'));

		Director::test("DummyProcessor_GatewayHosted/complete/DummyGatewayHosted/$paymentID?$query");
		$payment = $payment = Payment::get()->byID($paymentID);
		$this->assertEquals($payment->Status, Payment::SUCCESS);
	}

	public function testPaymentFailure() {
		$this->processor->capture($this->data);
		$paymentID = $this->processor->payment->ID;
		$this->returnURL .= "/$paymentID";

		$query = http_build_query(array(
			'Status' => 'Failure',
			'ErrorMessage' => 'Internal Server Error',
			'ErrorCode' => '101'
		));

		Director::test("DummyProcessor_GatewayHosted/complete/DummyGatewayHosted/$paymentID?$query");
		$payment = $payment = Payment::get()->byID($paymentID);
		$this->assertEquals($payment->Status, Payment::FAILURE);
		$this->assertEquals($payment->ErrorMessage, 'Internal Server Error');
		$this->assertEquals($payment->ErrorCode, '101');
	}

	public function testPaymentIncomplete() {
		$this->processor->capture($this->data);
		$paymentID = $this->processor->payment->ID;
		$this->returnURL .= "/$paymentID";

		$query = http_build_query(array(
			'Status' => 'Incomplete',
			'ErrorMessage' => 'Awaiting payment confirmation',
			'ErrorCode' => '102'
		));

		Director::test("DummyProcessor_GatewayHosted/complete/DummyGatewayHosted/$paymentID?$query");
		$payment = $payment = Payment::get()->byID($paymentID);
		$this->assertEquals($payment->Status, Payment::INCOMPLETE);
		$this->assertEquals($payment->ErrorMessage, 'Awaiting payment confirmation');
	}
}