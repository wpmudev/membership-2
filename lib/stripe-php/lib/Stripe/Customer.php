<?php

class M2_Stripe_Customer extends M2_Stripe_ApiResource
{
  /**
   * @param string $id The ID of the customer to retrieve.
   * @param string|null $apiKey
   *
   * @return Stripe_Customer
   */
  public static function retrieve($id, $apiKey=null)
  {
    $class = get_class();
    return self::_scopedRetrieve($class, $id, $apiKey);
  }

  /**
   * @param array|null $params
   * @param string|null $apiKey
   *
   * @return array An array of Stripe_Customers.
   */
  public static function all($params=null, $apiKey=null)
  {
    $class = get_class();
    return self::_scopedAll($class, $params, $apiKey);
  }

  /**
   * @param array|null $params
   * @param string|null $apiKey
   *
   * @return Stripe_Customer The created customer.
   */
  public static function create($params=null, $apiKey=null)
  {
    $class = get_class();
    return self::_scopedCreate($class, $params, $apiKey);
  }

  /**
   * @returns Stripe_Customer The saved customer.
   */
  public function save()
  {
    $class = get_class();
    return self::_scopedSave($class);
  }

  /**
   * @param array|null $params
   *
   * @returns Stripe_Customer The deleted customer.
   */
  public function delete($params=null)
  {
    $class = get_class();
    return self::_scopedDelete($class, $params);
  }

  /**
   * @param array|null $params
   *
   * @returns Stripe_InvoiceItem The resulting invoice item.
   */
  public function addInvoiceItem($params=null)
  {
    if (!$params)
      $params = array();
    $params['customer'] = $this->id;
    $ii = M2_Stripe_InvoiceItem::create($params, $this->_apiKey);
    return $ii;
  }

  /**
   * @param array|null $params
   *
   * @returns array An array of the customer's Stripe_Invoices.
   */
  public function invoices($params=null)
  {
    if (!$params)
      $params = array();
    $params['customer'] = $this->id;
    $invoices = M2_Stripe_Invoice::all($params, $this->_apiKey);
    return $invoices;
  }

  /**
   * @param array|null $params
   *
   * @returns array An array of the customer's Stripe_InvoiceItems.
   */
  public function invoiceItems($params=null)
  {
    if (!$params)
      $params = array();
    $params['customer'] = $this->id;
    $iis = M2_Stripe_InvoiceItem::all($params, $this->_apiKey);
    return $iis;
  }

  /**
   * @param array|null $params
   *
   * @returns array An array of the customer's Stripe_Charges.
   */
  public function charges($params=null)
  {
    if (!$params)
      $params = array();
    $params['customer'] = $this->id;
    $charges = M2_Stripe_Charge::all($params, $this->_apiKey);
    return $charges;
  }

  /**
   * @param array|null $params
   *
   * @returns Stripe_Subscription The updated subscription.
   */
  public function updateSubscription($params=null)
  {
    $requestor = new M2_Stripe_ApiRequestor($this->_apiKey);
    $url = $this->instanceUrl() . '/subscription';
    list($response, $apiKey) = $requestor->request('post', $url, $params);
    $this->refreshFrom(array('subscription' => $response), $apiKey, true);
    return $this->subscription;
  }

  /**
   * @param array|null $params
   *
   * @returns Stripe_Subscription The cancelled subscription.
   */
  public function cancelSubscription($params=null)
  {
    $requestor = new M2_Stripe_ApiRequestor($this->_apiKey);
    $url = $this->instanceUrl() . '/subscription';
    list($response, $apiKey) = $requestor->request('delete', $url, $params);
    $this->refreshFrom(array('subscription' => $response), $apiKey, true);
    return $this->subscription;
  }

  /**
   * @param array|null $params
   *
   * @returns Stripe_Customer The updated customer.
   */
  public function deleteDiscount()
  {
    $requestor = new M2_Stripe_ApiRequestor($this->_apiKey);
    $url = $this->instanceUrl() . '/discount';
    list($response, $apiKey) = $requestor->request('delete', $url);
    $this->refreshFrom(array('discount' => null), $apiKey, true);
  }
}
