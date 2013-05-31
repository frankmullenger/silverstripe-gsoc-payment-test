<?php

class DummyGatewayHostedTest extends FunctionalTest {

	public $data;
	public $processor;

	public function setUp() {
		parent::setUp();

		$paymentMethods = array('test' => array('DummyGatewayHosted'));
		Config::inst()->remove('PaymentProcessor', 'supported_methods');
		Config::inst()->update('PaymentProcessor', 'supported_methods', $paymentMethods);

		Config::inst()->remove('PaymentGateway', 'environment');
		Config::inst()->update('PaymentGateway', 'environment', 'test');

		$this->processor = PaymentFactory::factory('DummyGatewayHosted');

		$this->data = array(
			'Amount' => '10',
			'Currency' => 'USD'
		);
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

		//This should set up a redirect to the gateway for the browser in the response of the controller
		$this->processor->capture($this->data);

		//Test redirect to gateway
		$response = Controller::curr()->getResponse();
		$gatewayURL = $this->processor->gateway->gatewayURL;
		
		$queryString = http_build_query(array(
			'Amount' => $this->data['Amount'],
			'Currency' => $this->data['Currency'],
			'ReturnURL' => $this->processor->gateway->returnURL
		));
		$this->assertEquals($response->getHeader('Location'), '/dummy/external/pay?' . $queryString);
		
		//Test payment completion after redirect from gateway
		$queryString = http_build_query(array('Status' => 'Success'));
		Director::test($this->processor->gateway->returnURL . "?$queryString");
		
		$payment = $payment = Payment::get()->byID($this->processor->payment->ID);
		$this->assertEquals($payment->Status, Payment::SUCCESS);
	}

	public function testPaymentFailure() {
		
		$this->processor->capture($this->data);
		
		//Test redirect to the gateway
		$response = Controller::curr()->getResponse();
		$gatewayURL = $this->processor->gateway->gatewayURL;
		
		$queryString = http_build_query(array(
			'Amount' => $this->data['Amount'],
			'Currency' => $this->data['Currency'],
			'ReturnURL' => $this->processor->gateway->returnURL
		));
		$this->assertEquals($response->getHeader('Location'), '/dummy/external/pay?' . $queryString);

		//Test payment completion after redirect from gateway
		$queryString = http_build_query(array(
			'Status' => 'Failure',
			'ErrorMessage' => 'Payment Gateway API Error',
			'ErrorCode' => '12345'
		));
		Director::test($this->processor->gateway->returnURL . "?$queryString");

		$payment = $payment = Payment::get()->byID($this->processor->payment->ID);
		$this->assertEquals($payment->Status, Payment::FAILURE);
		
		$error = $payment->Errors()->first();
		$this->assertEquals($error->ErrorMessage, 'Payment Gateway API Error');
		$this->assertEquals($error->ErrorCode, '12345');
	}

	public function testPaymentIncomplete() {
		
		$this->processor->capture($this->data);
		
		//Test redirect to the gateway
		$response = Controller::curr()->getResponse();
		$gatewayURL = $this->processor->gateway->gatewayURL;
		
		$queryString = http_build_query(array(
			'Amount' => $this->data['Amount'],
			'Currency' => $this->data['Currency'],
			'ReturnURL' => $this->processor->gateway->returnURL
		));
		$this->assertEquals($response->getHeader('Location'), '/dummy/external/pay?' . $queryString);
		
		//Test payment completion after redirect from gateway
		$queryString = http_build_query(array(
			'Status' => 'Incomplete',
			'ErrorMessage' => 'Awaiting Payment Confirmation',
			'ErrorCode' => '54321'
		));
		Director::test($this->processor->gateway->returnURL . "?$queryString");

		$payment = $payment = Payment::get()->byID($this->processor->payment->ID);
		$this->assertEquals($payment->Status, Payment::INCOMPLETE);
		
		$error = $payment->Errors()->first();
		$this->assertEquals($error->ErrorMessage, 'Awaiting Payment Confirmation');
		$this->assertEquals($error->ErrorCode, '54321');
	}
}