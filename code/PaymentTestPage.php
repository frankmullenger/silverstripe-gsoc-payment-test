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

  public static $paymentMethod = "Dummy";

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
    $supported_methods = Payment_Controller::get_supported_methods();
    $fields->push(new DropDownField(
      'payment_controller', 
      'Select Payment Method', 
      $supported_methods
    ));

    $paymentFields = Payment_Controller::get_combined_form_fields();

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
    $paymentMethod = $data['PaymentMethod'];
    $paymentController = new $paymentMethod();

    $paymentController->processRequest($data);
  }
}