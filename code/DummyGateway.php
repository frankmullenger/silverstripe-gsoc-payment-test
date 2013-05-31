<?php

/**
 * Dummy merchant hosted payment gateway.
 */
class DummyGateway_MerchantHosted extends PaymentGateway_MerchantHosted {

	protected $supportedCardTypes = array(
		'visa' => 'Visa',
		'master' => 'Mastercard'
	);

	protected $supportedCurrencies = array(
		'USD' => 'United States Dollar',
		'GBP' => 'Great British Pound'
	);

	/**
	 * Override to mock up data validation
	 *
	 * @see PaymentGateway::validate()
	 *
	 * @param Array $data
	 * @return ValidationResult
	 */
	public function validate($data) {
		
		$result = parent::validate($data);
		if (isset($data['Amount'])) {
			$amount = $data['Amount'];
			$cents = round($amount - intval($amount), 2);

			switch ($cents) {
				case 0.11:
				$result->error('Cents value is .11');
				break;
			}

			$this->validationResult = $result;
		}

		return $result;
	}

	/**
	 * @see PaymentGateway::process()
	 */
	public function process($data) {
		//Mimic failures, like a gateway response such as 404, 500 etc.
		$amount = $data['Amount'];
		$cents = round($amount - intval($amount), 2);

		switch ($cents) {
			case 0.01:
				return new PaymentGateway_Failure(new SS_HTTPResponse('Connection Error', 500));
			case 0.02:
				return new PaymentGateway_Failure(null, array('1A' => 'Payment cannot be completed'));
			case 0.03:
				return new PaymentGateway_Incomplete(null, array('1B' => 'Awaiting payment confirmation'));
			default:
				return new PaymentGateway_Success();
		}
	}
}

/**
 * Dummy gateway hosted payment gateway.
 */
class DummyGateway_GatewayHosted extends PaymentGateway_GatewayHosted {

	protected $supportedCardTypes = array(
		'visa' => 'Visa',
		'master' => 'Mastercard'
	);

	protected $supportedCurrencies = array(
		'USD' => 'United States Dollar',
		'GBP' => 'Great British Pound',
		'NZD' => 'New Zealand Dollars'
	);

	public function __construct() {
		$this->gatewayURL = Director::baseURL() . 'dummy/external/pay';
	}

	/**
	 * Override to mock up validation
	 *
	 * @see PaymentGateway::validate()
	 *
	 * @param Array $data
	 * @return ValidationResult
	 */
	public function validate($data) {
		$result = parent::validate($data);

		$amount = $data['Amount'];
		$cents = round($amount - intval($amount), 2);
		switch ($cents) {
			case 0.11:
				$result->error('Cents value is .11');
				$result->error('This is another error message for cents = .11');
				break;
		}
		return $result;
	}

	/**
	 * @see PaymentGateay::process()
	 */
	public function process($data) {
		$postData = array(
			'Amount' => $data['Amount'],
			'Currency' => $data['Currency'],
			'ReturnURL' => $this->returnURL
		);

		//Mimic HTTP failure
		$amount = $data['Amount'];
		$cents = round($amount - intval($amount), 2);

		if ($cents == 0.01) {
			return new PaymentGateway_Failure(new SS_HTTPResponse('Connection Error', 500));
		}

		$queryString = http_build_query($postData);
		Controller::curr()->redirect($this->gatewayURL . '?' . $queryString);
	}

	/**
	 * @see PaymentGateway_GatewayHosted::response()
	 */
	public function check($request) {

		switch($request->getVar('Status')) {
			case 'Failure':
				return new PaymentGateway_Failure(
					null,
					array($request->getVar('ErrorCode') => $request->getVar('ErrorMessage'))
				);
				break;
			case 'Incomplete':
				return new PaymentGateway_Incomplete(
					null,
					array($request->getVar('ErrorCode') => $request->getVar('ErrorMessage'))
				);
				break;
			case 'Success':
			default:
				return new PaymentGateway_Success();
				break;
		}
	}
}

/**
 * Controller class representing a dummy external payment gateway for processing
 * dummy payments.
 * 
 * @see DummyGateway_GatewayHosted
 */
class DummyGateway_Controller extends ContentController {
	
	/**
	 * Controller action for showing payment form
	 *
	 * @param SS_HTTPRequest
	 */
	public function pay($request) {
		return array(
			'Content' => "<h1>Fill out this form to make payment</h1>",
			'Form' => $this->PayForm()
		);
	}

	/**
	 * Return the payment form
	 */
	public function PayForm() {
		$request = $this->getRequest();

		$fields = new FieldList(
			new TextField('Amount', 'Amount', $request->getVar('Amount')),
			new TextField('Currency', 'Currency', $request->getVar('Currency')),
			new TextField('CardHolderName', 'Card Holder Name', 'Test Testoferson'),
			new CreditCardField('CardNumber', 'Card Number', '1234567812345678'),
			new TextField('DateExpiry', 'Expiration date', '12/15'),
			new TextField('ReturnURL', 'ReturnURL', $request->getVar('ReturnURL'))
		);

		$actions = new FieldList(
			new FormAction("dopay", 'Process Payment')
		);

		//TODO validate this form

		return new Form($this, "PayForm", $fields, $actions);
	}

	/**
	 * Make the payment after the form is submitted
	 * Redirect back to DummyGateway with the dummy response
	 */
	public function dopay($data, $form) {
		$returnURL = $data['ReturnURL'];
		if (! $returnURL) {
			user_error("Return URL is not set for this transaction", E_USER_ERROR);
		}

		$amount = $data['Amount'];
		$cents = round($amount - intval($amount), 2);
		switch ($cents) {
			case 0.02:
				$returnArray = array(
					'Status' => 'Failure',
					'ErrorMessage' => 'Internal Server Error',
					'ErrorCode' => '101'
				);
				break;
			case 0.03:
				$returnArray = array(
					'Status' => 'Incomplete',
					'ErrorMessage' => 'Awaiting payment confirmation',
					'ErrorCode' => '102'
				);
				break;
			default:
				$returnArray = array('Status' => 'Success');
				break;
		}

		$queryString = http_build_query($returnArray);
		Controller::curr()->redirect($returnURL . '?' . $queryString);
	}
}