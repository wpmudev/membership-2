<?php

class M2_Stripe_Subscription extends M2_Stripe_ApiResource
{
  /**
   * @return string The API URL for this Stripe subscription.
   */
  public function instanceUrl()
  {
    $id = $this['id'];
    $customer = $this['customer'];
    $class = get_class($this);
    if (!$id) {
      throw new M2_Stripe_InvalidRequestError(
          "Could not determine which URL to request: " .
          "class instance has invalid ID: $id",
          null
      );
    }
    $id = M2_Stripe_ApiRequestor::utf8($id);
    $customer = M2_Stripe_ApiRequestor::utf8($customer);

    $base = self::classUrl('M2_Stripe_Customer');
    $customerExtn = urlencode($customer);
    $extn = urlencode($id);
    return "$base/$customerExtn/subscriptions/$extn";
  }

  /**
   * @param array|null $params
   * @return Stripe_Subscription The deleted subscription.
   */
  public function cancel($params=null)
  {
    $class = get_class();
    return self::_scopedDelete($class, $params);
  }

  /**
   * @return Stripe_Subscription The saved subscription.
   */
  public function save()
  {
    $class = get_class();
    return self::_scopedSave($class);
  }

  /**
   * @return Stripe_Subscription The updated subscription.
   */
  public function deleteDiscount()
  {
    $requestor = new M2_Stripe_ApiRequestor($this->_apiKey);
    $url = $this->instanceUrl() . '/discount';
    list($response, $apiKey) = $requestor->request('delete', $url);
    $this->refreshFrom(array('discount' => null), $apiKey, true);
  }
}
