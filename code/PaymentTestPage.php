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
    
    return new Form($this, 'ProcessForm', $fields, $actions);
  }
  
  function proceed($data, $form) {
    Session::set('PaymentMethod', $data['PaymentMethod']);
    
    return $this->customise(array(
      'Content' => $this->Content,
      'Form' => $this->OrderForm()
    ))->renderWith('Page');
  }
  
  function OrderForm() {
    $paymentMethod = Session::get('PaymentMethod');
    
    try {
      $processor = PaymentFactory::factory($paymentMethod);
    } catch (Exception $e) {
      $fields = new FieldList(array(new ReadonlyField($e->getMessage())));
      $actions = new FieldList();
      return new Form($this, 'OrderForm', $fields, $actions);
    }
    
    $fields = $processor->getFormFields();
    $fields->push(new HiddenField('PaymentMethod', 'PaymentMethod', $paymentMethod));
    
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
      $paymentController = PaymentFactory::factory($paymentMethod);
    } catch (Exception $e) {
      return $this->customise(array(
        'Content' => $e->getMessage()  
      ))->renderWith('Page');  
    }
    
    try {
      $paymentController->setRedirectURL($this->link() . 'complete');
      $paymentController->processRequest($data);
    } catch (Exception $e) {
      return $this->customise(array(
        'Content' => $e->getMessage()
      ))->renderWith('Page');
    }
  }
  
  /**
   * Show a page after a payment is completed 
   */
  function complete() {
    $paymentID = Session::get('PaymentID');
    $payment = Payment::get()->byID($paymentID);
    
    return $this->customise(array(
      'Content' => 'Payement completed. Status: ' . $payment->Status   
    ))->renderWith('Page');
  }
}

