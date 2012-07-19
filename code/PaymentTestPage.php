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
       'Form' => $this->OrderForm() 
    );
  }

  /**
   * Get the order form for processing a dummy payment
   */
  function OrderForm() {
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

    $paymentFields = PaymentProcessor::get_combined_form_fields();

    if ($paymentFields && $paymentFields->exists()) foreach ($paymentFields as $paymentField) {
      $fields->push($paymentField);
    }

    $actions = new FieldList(
      new FormAction('processOrder', 'Place order')
    );
    
    return new Form($this, 'OrderForm', $fields, $actions);
  }
  
  /**
   * Process order
   */
  function processOrder($data, $form) {

    SS_Log::log(new Exception(print_r($data, true)), SS_Log::NOTICE);

    $paymentMethod = $data['PaymentMethod'];
    $paymentController = PaymentFactory::factory($paymentMethod);

    SS_Log::log(new Exception(print_r($paymentController, true)), SS_Log::NOTICE);

    return $paymentController->processRequest($data);
  }
}