<?php

namespace App\Http\Controllers;
use App\Models\Camera;
use Illuminate\Http\Request;
use SoapClient;
/**
 *  Class for CamerasNeoface
 */
class CameraController extends Controller
{
    
    public function __construct(){}

    /**
     *  Funcion for return all users
     */
    public function all(Request $request)
    {

        try {
            
            try {
                $ip = $request['ip'];
                $port = $request['port'];
                $user = $request['user'];
                $password = $request['pass'];

               /*  $urlsoap = $request["urlsoap"];
                $configsoap = [
                    'login' =>  $request["login"],
                    'password' =>  $request["password"],
                    'exceptions' => true,
                ]; */

                $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');

                $configsoap = [
                    'login' => $user,
                    'password' =>  $password,
                    'exceptions' => true,
                ];

                $client = new SoapClient($urlsoap, $configsoap);
                /* $client = new SoapClient("http://172.20.96.233:8790/".config('constants.urlsoap'), config('constants.configsoap')); */
                $result = $client->__soapCall("GetCameras", array());
                $result = $result->GetCamerasResult->CameraInfo;


                
                
                // Create structured array
                $data = array();


                foreach ($result as $value) {
                    $camera = new Camera();
                    $camera->CameraId = $value->CameraId;
                    $camera->IPAddress = $value->IPAddress; 
                    $camera->Name = $value->Name; 
                    $camera->Description = $value->Description; 
                    $camera->LiveViewIPAddress = $value->LiveViewIPAddress; 
                    $camera->LiveViewPort = $value->LiveViewPort; 
                    array_push($data, $camera);
                }

                
                // Return all camera information
                return response() -> json(
                    array('data' => $data, 'message' => config('constants.messages.3.message')),
                    config('constants.messages.3.code')
                );
                
            } catch (SoapFault $e) {                
                return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
            }

        } catch (\Throwable $th) {
            return $th;
            //return response() -> json(config('constants.messages.1.message'), config('constants.messages.1.code'));
        }
    }


}
