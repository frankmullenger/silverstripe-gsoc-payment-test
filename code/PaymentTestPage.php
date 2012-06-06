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

    $fields = new FieldList();

    //$dummyPayment = singleton('Dummy_Payment');
    //SS_Log::log(new Exception(print_r($dummyPayment, true)), SS_Log::NOTICE);

    //TODO For each payment method that is enabled get the form fields
    //old way was Payment::get_combined_form_fields() - is there a better way now?
    //maybe we can use DI for this also?

    $paymentFields = singleton('Dummy_Payment')->getFormFields();
    //SS_Log::log(new Exception(print_r($paymentFields->map(), true)), SS_Log::NOTICE);

    //TODO should we wrap payment fields in a CompositeField so we can just
    //$fields->push($paymentFields);
    //or is that too restrictive from a frontend perspective?

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
    $paymentController = Payment_Factory::createController($paymentMethod);

    $result = $paymentController->processRequest($data);

    return $this->customise(array(
      "Content" => 'Payment #' . $result->ID . ' status:' . $result->Status,
      "Form" => '',
    ))->renderWith("Page");
  }

}