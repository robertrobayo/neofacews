<?php

namespace App\Http\Controllers;
use Aw\Nusoap\NusoapClient as nusoap_client;

class ExampleController extends Controller
{
    
    public function __construct(){}

    public function substract($num1, $num2)
    {
        $url = config('constants.urlsoap');
        try {

            $client = new nusoap_client($url, true);
            $err = $client->getError();

            $param = array('intA' => $num1,'intB' => $num2);
            $result = $client->call('Subtract', $param);
            
            return response() -> json(
                array('data' => $result, 'message' => config('constants.messages.3.message')),
                config('constants.messages.3.code')
            );

        } catch (\Throwable $th) {
            return response() -> json(config('constants.messages.1.message'), config('constants.messages.1.code'));
        }
    }

}
