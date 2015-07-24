<?php

class M2_Stripe_List extends M2_Stripe_Object
{
  public function all($params=null)
  {
    $requestor = new M2_Stripe_ApiRequestor($this->_apiKey);
    list($response, $apiKey) = $requestor->request(
        'get',
        $this['url'],
        $params
    );
    return M2_Stripe_Util::convertToStripeObject($response, $apiKey);
  }

  public function create($params=null)
  {
    $requestor = new M2_Stripe_ApiRequestor($this->_apiKey);
    list($response, $apiKey) = $requestor->request(
        'post', $this['url'], $params
    );
    return M2_Stripe_Util::convertToStripeObject($response, $apiKey);
  }

  public function retrieve($id, $params=null)
  {
    $requestor = new M2_Stripe_ApiRequestor($this->_apiKey);
    $base = $this['url'];
    $id = M2_Stripe_ApiRequestor::utf8($id);
    $extn = urlencode($id);
    list($response, $apiKey) = $requestor->request(
        'get', "$base/$extn", $params
    );
    return M2_Stripe_Util::convertToStripeObject($response, $apiKey);
  }

}
