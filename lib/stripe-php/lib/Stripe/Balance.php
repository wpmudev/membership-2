<?php

class M2_Stripe_Balance extends M2_Stripe_SingletonApiResource
{
  /**
    * @param string|null $apiKey
    *
    * @return Stripe_Balance
    */
  public static function retrieve($apiKey=null)
  {
    $class = get_class();
    return self::_scopedSingletonRetrieve($class, $apiKey);
  }
}
