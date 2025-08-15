<?php

class DuitkuPop
{
  public static function createInvoice($url, $params, $headers)
  {
    $result = Duitku_ApiRequestor::post($url, $params, $headers);
    return $result;
  }
}
