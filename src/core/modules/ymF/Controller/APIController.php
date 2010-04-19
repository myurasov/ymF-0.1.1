<?php

namespace ymF\Controller;

class APIController extends ControllerBase
{
  protected static function APIResult($result = null, $error = \ymF\ERROR_OK, $message = '')
  {
    // Create API result
    $api_result = array('error' => $error);
    is_null($result) or $api_result['result'] = $result;
    $message == '' or $api_result['message'] = $message;

    return $api_result;
  }
}