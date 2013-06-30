<?php

class PaymentTestPage extends Page {

  /**
   * TODO Could use to create default payment test page when /dev/build is run
   */
  function requireDefaultRecords() {
    parent::requireDefaultRecords();
  }
}

class PaymentTestPage_Controller extends Page_Controller {

  function index() {
    return array( 
       'Content' => $this->Content, 
       'Form' => $this->ProcessForm() 
    );
  }

  /**
   * Get the order form for processing a dummy payment
   */
  function ProcessForm() {
    $fields = new FieldList;

    // Create a dropdown select field for choosing gateway
    $supported_methods = PaymentProcessor::get_supported_methods();

    $source = array();
    foreach ($supported_methods as $methodName) {
      $methodConfig = PaymentFactory::get_factory_config($methodName);
      $source[$methodName] = $methodConfig['title'];
    }

    $fields->push(new DropDownField(
      'PaymentMethod', 
      'Select Payment Method', 
      $source
    ));

    $actions = new FieldList(
      new FormAction('proceed', 'Proceed')
    );
    
    $processForm = new Form($this, 'ProcessForm', $fields, $actions);
    $processForm->disableSecurityToken();
    return $processForm;
  }
  
  function proceed($data, $form) {

    if (isset($data['PaymentMethod'])) Session::set('PaymentMethod', $data['PaymentMethod']);

    return array(
      'Content' => $this->Content,
      'Form' => $this->OrderForm()
    );
  }
  
  function OrderForm() {

    $paymentMethod = Session::get('PaymentMethod');

    try {
      $processor = PaymentFactory::factory($paymentMethod);
    } 
    catch (Exception $e) {
      $fields = new FieldList(array(new ReadonlyField($e->getMessage())));
      $actions = new FieldList();
      return new Form($this, 'OrderForm', $fields, $actions);
    }
    
    $fields = $processor->getFormFields();
    $fields->push(new TextField('PaymentMethod', 'PaymentMethod', $paymentMethod));
    
    $actions = new FieldList(
      new FormAction('processOrder', 'Process Order')  
    ); 

    $validator = $processor->getFormRequirements();

    return new Form($this, 'OrderForm', $fields, $actions, $validator);
  }
  
  /**
   * Process order
   */
  function processOrder($data, $form) {
    $paymentMethod = $data['PaymentMethod'];
    
    try {
      $paymentProcessor = PaymentFactory::factory($paymentMethod);
    } 
    catch (Exception $e) {
      return array(
        'Content' => $e->getMessage() 
      );
    }

    try {

      $paymentProcessor->setRedirectURL($this->link() . 'completed');
      $paymentProcessor->capture($data);
    } 
    catch (Exception $e) {

      //This is where we catch gateway validation or gateway unreachable errors
      $result = $paymentProcessor->gateway->getValidationResult();
      $payment = $paymentProcessor->payment;

      return array(
        'Content' => $this->customise(array(
          'ExceptionMessage' => $e->getMessage(),
          'ValidationMessage' => $result->message(),
          'OrderForm' => $this->OrderForm(),
          'Payment' => $payment
        ))->renderWith('PaymentTestPage')
      );
    }
  }
  
  /**
   * Show a page after a payment is completed 
   */
  function completed() {

    $paymentID = Session::get('PaymentID');
    $payment = Payment::get()->byID($paymentID);

    return array(
      'Content' => $this->customise(array(
        'Payment' => $payment
      ))->renderWith('PaymentTestPage')
    );
  }
}

