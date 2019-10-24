<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\EnrolSubject;
use App\Models\DeleteSubject;
use App\Models\UpdateSubject;
use App\Models\Image;
use App\Models\SecurityLevel;
use App\Models\Watchlist;
use App\Models\AddSubjectPhoto;
use App\Models\MatchFeatureByWatchlist;
use App\Models\Match;
use App\Models\WatchListIDS;
use App\Models\GetSubjectEnrolmentFaceFromPhoto;
use App\Models\GetFaceImage;
use App\Models\GetSubjectPhoto;
use Illuminate\Http\Request;
use SoapClient;
/**
 *  Class for UsersNeoface
 */
class UserController extends Controller
{
    
    public function __construct(){}

    /**
     *  Funcion for return all users
     */
    public function all(Request $request)
    {            
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
                    'connection_timeout'=> 10,
                    
                ];
                try {    
                    ini_set('default_socket_timeout', 10);
                    $result-> $client = new SoapClient($urlsoap, $configsoap);
                    
                    $result = $client->__soapCall("ListSubjects", array());
                    
                    $result = $result->ListSubjectsResult->SubjectInfo;

                    // Create structured array
                    $data = array();

                    foreach ($result as $value) {
                        $user = new User();
                        $user->SubjectId = $value->SubjectId;                    
                        $user->EnrolmentDate = date("Y-m-d H:i:s", strtotime($value->EnrolmentDate));
                        $user->FirstName = utf8_encode($value->FirstName);
                        $user->LastName = utf8_encode($value->LastName);
                        $user->MiddleName = utf8_encode($value->MiddleName);
                        $user->Notes = utf8_encode($value->Notes);
                        
                        $watchList = new Watchlist();
                        $watchList->WatchlistId = $value->Watchlist->WatchlistId;
                        $watchList->Colour = $value->Watchlist->Colour;
                        $watchList->Description = utf8_encode($value->Watchlist->Description);
                        $watchList->Default = $value->Watchlist->IsDefault;
                        $watchList->Replica = $value->Watchlist->IsReplica;
                        $watchList->Name = utf8_encode($value->Watchlist->Name);

                        $securityLevel = new SecurityLevel();

                        $user->Watchlist = $watchList;

                        array_push($data, $user);
                    }

                // Return all user information
                return response() -> json(
                    array('data' => $data, 'message' => config('constants.messages.3.message')),
                    config('constants.messages.3.code')
                );

            } catch (SoapFault $e) {
                return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
            }
        } catch (\Throwable $th) {
            return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
        }
    }


    /**
     *  Search one user in system
     */
    public function find($guid, Request $request)
    {
        try {

            $ip = $request['ip'];
            $port = $request['port'];
            $user = $request['user'];
            $password = $request['pass'];
            
            $subject = array(
                'subject' => array(
                    'SubjectId' => $guid
                )
            );

            $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');

            $configsoap = [
                'login' => $user,
                'password' =>  $password,
                'exceptions' => true,
                'connection_timeout'=> 10,
            ];
            try { 
                ini_set('default_socket_timeout', 10);
                $client = new SoapClient($urlsoap, $configsoap);
                $result = $client->__soapCall("GetSubject", array($subject));
                $value = $result->GetSubjectResult;

                // Create structured array
                $data = $value;

                $user = new User();
                $user->SubjectId = $value->SubjectId;                    
                $user->EnrolmentDate = date("Y-m-d H:i:s", strtotime($value->EnrolmentDate));
                $user->FirstName = utf8_encode($value->FirstName);
                $user->LastName = utf8_encode($value->LastName);
                $user->MiddleName = utf8_encode($value->MiddleName);
                $user->Notes = utf8_encode($value->Notes);
                    
                $watchList = new Watchlist();
                $watchList->WatchlistId = $value->Watchlist->WatchlistId;
                $watchList->Colour = $value->Watchlist->Colour;
                $watchList->Description = utf8_encode($value->Watchlist->Description);
                $watchList->Default = $value->Watchlist->IsDefault;
                $watchList->Replica = $value->Watchlist->IsReplica;
                $watchList->Name = utf8_encode($value->Watchlist->Name);

                $securityLevel = new SecurityLevel();
                $user->Watchlist = $watchList;

                // Return all user information
                return response() -> json(
                    array('data' => $data, 'message' => config('constants.messages.3.message')),
                    config('constants.messages.3.code')
                );

            } catch (SoapFault $e) {
                return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
            }

        } catch (\Throwable $th) {
            return response() -> json(config('constants.messages.1.message'), config('constants.messages.1.code'));
        }
        
    }


    /**
     *  Enroll subject
     * - WatchlistId
     * - FirstName
     * - LastName
     * - MiddleName
     * - Notes
     * - Title
     * - Photo
     */
     function enrol(Request $request)
     {
   
        try {
            // Global variable for upload file
            $file;

            // If Upload file photo
            if ($request->file("Photo")->isValid()) 
            {
                $file = $request -> file("Photo");
                $ext = $file -> getClientOriginalExtension();
                // If file extension is correct
                if( $ext == "jpg" ||  $ext == "png"  ||  $ext == "jpeg" ){
                    $file = file_get_contents($file);
                }else{
                    return response() -> json(config('constants.messages.5.message'), config('constants.messages.5.code'));
                }
            }else{
                return response() -> json(config('constants.messages.6.message'), config('constants.messages.6.code'));
            }

            $securityLevel = new SecurityLevel();
            $securityLevel->Description = "Default Security Level";
            $securityLevel->Level = 0;
            $securityLevel->Name = "Default";

            $watchList = new WatchList();
            $watchList->WatchlistId = $request["WatchlistId"];
            $watchList->SecurityLevel = array($securityLevel);

            $user = new User();
            $user->SubjectId = $request["Guid"];
            $user->Enabled = true;
            $user->EnrolmentDate = date("Y-m-d\Th:i:s");
            $user->FirstName = $request["FirstName"];
            $user->LastName = $request["LastName"];
            $user->MiddleName = $request["MiddleName"];
            $user->Notes = $request["Notes"];
            $user->Title = $request["Title"];
            $user->SecurityLevel = array($securityLevel);
            $user->Watchlist = array($watchList);

            $image = new Image();
            $image->Image = $file;
            $image->ImageId = GUID();

            // Create object Enrol Subject
            $enrolSubject = new EnrolSubject();
            $enrolSubject->subject = $user;
            $enrolSubject->image = $image;
            $enrolSubject->watchlist = $watchList;
            $enrolSubject->preventDuplicateEnrolment = 0;

            $ip = $request['ip'];
            $port = $request['port'];
            $user = $request['user'];
            $pass = $request['pass'];
        
            $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');
                
            $configsoap = [
                'login' => $user,
                'password' =>  $pass,
                'exceptions' => true,
                'connection_timeout'=> 5,
            ];
                try {
                    ini_set('default_socket_timeout', 5);
                    $client = new SoapClient($urlsoap, $configsoap);

                    $response = $client->__soapCall("EnrolSubject", array($enrolSubject));
                    
                    // If response success
                    if($response->EnrolSubjectResult->Error->Type == "Success")
                    {
                    return response() -> json(config('constants.messages.4.message'), config('constants.messages.4.code'));
                    }else{
                        return response() -> json($response->EnrolSubjectResult->Error->Message, config('constants.messages.2.code'));
                    }
    
                } catch (SoapFault $e) {
                    return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
                }

        } catch (\Throwable $th) {
            return $th;
            return response() -> json(config('constants.messages.1.message'), config('constants.messages.1.code'));
        }

    }

    /** 
     *  Update neoface user
     */
    function update(Request $request)
    {
        try {
            $securityLevel = new SecurityLevel();
            $securityLevel->Description = "Default Security Level";
            $securityLevel->Level = 0;
            $securityLevel->Name = "Default";

            $watchList = new WatchList();
            $watchList->WatchlistId = $request["WatchlistId"];
            $watchList->SecurityLevel = array($securityLevel);

            $user = new User();
            $user->SubjectId = $request["Guid"];
            $user->EnrolmentDate = date("Y-m-d\Th:i:s");
            $user->Enabled = true;
            $user->FirstName = $request["FirstName"];
            $user->LastName = $request["LastName"];
            $user->MiddleName = $request["MiddleName"];
            $user->Notes = $request["Notes"];
            $user->Title = $request["Title"];
            $user->SecurityLevel = array($securityLevel);
            $user->Watchlist = $watchList;

            // Create object Enrol Subject
            $updateSubject = new UpdateSubject();
            $updateSubject->subject = $user;
            
            try {
                $ip = $request['ip'];
                $port = $request['port'];
                $user = $request['user'];
                $pass = $request['pass'];
            
                $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');

                $configsoap = [
                    'login' =>  $user,
                    'password' =>  $pass,
                    'exceptions' => true,
                ];

                $client = new SoapClient($urlsoap,$configsoap);
                $response = $client->__soapCall("UpdateSubject", array($updateSubject));
                
                // If response success
                if($response->UpdateSubjectResult->Type == "Success")
                {
                    return response() -> json(config('constants.messages.4.message'), config('constants.messages.4.code'));
                }else{
                    return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
                }
    
            } catch (SoapFault $e) {
                return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
            }

        } catch (\Throwable $th) {
            return $th;
            return response() -> json(config('constants.messages.1.message'), config('constants.messages.1.code'));
        }
    }

    /**
     *  Update Neoface Photo
     */

     function updatePhoto(Request $request)
     {
        try {

            // Global variable for upload file
            $file;

            // If Upload file photo
            if ($request->file("Photo")->isValid()) 
            {
                $file = $request -> file("Photo");
                $ext = $file -> getClientOriginalExtension();
                // If file extension is correct
                if( $ext == "jpg" ||  $ext == "png"  ||  $ext == "jpeg" ){
                    $file = file_get_contents($file);
                }else{
                    return response() -> json(config('constants.messages.5.message'), config('constants.messages.5.code'));
                }
            }else{
                return response() -> json(config('constants.messages.6.message'), config('constants.messages.6.code'));
            }

            $user = new User();
            $user->SubjectId = $request["Guid"];

            $image = new Image();
            $image->Image = $file;
            $image->ImageId = GUID();

            // Create object Enrol Subject
            $addSubjectPhoto = new AddSubjectPhoto();
            $addSubjectPhoto->subject = $user;
            $addSubjectPhoto->image = $image;

            try {

                $ip = $request['ip'];
                $port = $request['port'];
                $user = $request['user'];
                $pass = $request['pass'];
            
                $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');
                $configsoap = [
                    'login' =>  $user,
                    'password' =>  $pass,
                    'exceptions' => true,
                ];

                $client = new SoapClient($urlsoap, $configsoap);
                $response = $client->__soapCall("AddSubjectPhoto", array($addSubjectPhoto));
                
                // If response success
                if($response->AddSubjectPhotoResult->Type == "Success")
                {
                    return response() -> json(config('constants.messages.4.message'), config('constants.messages.4.code'));
                }else{
                    return response() -> json(config('constants.messages.6.message'), config('constants.messages.6.code'));
                }
                
    
            } catch (SoapFault $e) {
                return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
            }

        } catch (\Throwable $th) {
            return $th;
            return response() -> json(config('constants.messages.1.message'), config('constants.messages.1.code'));
        }
     }

    /**
     *  Delete permanently system user
     */
    function delete(Request $request)
    {   
        try {
            try {

                $ip = $request['ip'];
                $port = $request['port'];
                $user = $request['user'];
                $password = $request['pass'];

                $subject = array(
                    'subject' => array(
                        'SubjectId' => $request['guid']
                    )
                );

                $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');

                $configsoap = [
                    'login' => $user,
                    'password' =>  $password,
                    'exceptions' => true,
                ];


                $user = new User;
                $user->SubjectId = $request['guid'];
 
                $deleteSubject = new DeleteSubject;
                $deleteSubject->subject = $user;
                
                $client = new SoapClient($urlsoap, $configsoap);
                
                $response = $client->__soapCall("DeleteSubject", array($deleteSubject));
                
                // If response success
                if($response->DeleteSubjectResult->Type == "Success")
                {
                    return response() -> json(config('constants.messages.4.message'), config('constants.messages.4.code'));
                }else{
                    return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
                }
    
            } catch (SoapFault $e) {
                return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
            }

        } catch (\Throwable $th) {
            return response() -> json(config('constants.messages.1.message'), config('constants.messages.1.code'));
        }
    }

    /**
     *  Enable system user
     */
    function enable($guid)
    {
        try {
            
            try {

                $user = new User();
                $user->SubjectId = $guid;
                
                $ip = $request['ip'];
                $port = $request['port'];
                $user = $request['user'];
                $pass = $request['pass'];
            
                $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');

                $configsoap = [
                    'login' =>  $request["login"],
                    'password' =>  $request["password"],
                    'exceptions' => true,
                ];

                $client = new SoapClient($urlsoap, $configsoap);
                $response = $client->__soapCall("EnableSubject", array($enrolSubject));
                
                // If response success
                if($response->EnrolSubjectResult->Error->Type == "Success")
                {
                    return response() -> json(config('constants.messages.4.message'), config('constants.messages.4.code'));
                }else{
                    return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
                }
    
            } catch (SoapFault $e) {
                return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
            }

        } catch (\Throwable $th) {
            return response() -> json(config('constants.messages.1.message'), config('constants.messages.1.code'));
        }
    }


    function getImageMatch(Request $request){
        try {

            $ip = $request['ip'];
            $port = $request['port'];
            $user = $request['user'];
            $password = $request['pass'];

            $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');

            $configsoap = [
                'login' => $user,
                'password' =>  $password,
                'exceptions' => true,
            ];

            $idmatch = $request['idmatch'];
    
            $getFaceImage = new GetFaceImage;
            $getFaceImage->matchResultID =  $idmatch;
            
            $client = new SoapClient($urlsoap, $configsoap);
            $response = $client->__soapCall("GetFaceImage", array($getFaceImage));

            return response() -> json(
                array('data' => base64_encode($response->GetFaceImageResult), 'message' => config('constants.messages.3.message')),
                config('constants.messages.3.code')
            );
            
        }
        catch (SoapFault $e) {
            return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
        }
        
    }


    function getMatchBetweenTwoImages(Request $request){
        try {
            // $request = $request->json()->all();

            $ip = $request['ip'];
            $port = $request['port'];
            $user = $request['user'];
            $password = $request['pass'];

            $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');

            $configsoap = [
                'login' => $user,
                'password' =>  $password,
                'exceptions' => true,
            ];

            // // Global variable for upload file
            $file1;

            // If Upload file photo
            if ($request->file("file1")->isValid()) 
            {
                $file = $request -> file("file1");
                $ext = $file -> getClientOriginalExtension();

                // If file extension is correct
                if( $ext == "jpg" ||  $ext == "png"  ||  $ext == "jpeg" || $ext == "JPG" ||  $ext == "PNG"  ||  $ext == "JPEG" ){
                    $file1 = file_get_contents($file);
                }
                else
                {
                    return response() -> json(config('constants.messages.5.message'), config('constants.messages.5.code'));
                }
            }else{
                return response() -> json(config('constants.messages.6.message'), config('constants.messages.6.code'));
            }

            $file2;

            // If Upload file photo
            if ($request->file("file2")->isValid()) 
            {
                $file = $request -> file("file2");
                $ext = $file -> getClientOriginalExtension();
                // If file extension is correct
                if( $ext == "jpg" ||  $ext == "png"  ||  $ext == "jpeg" || $ext == "JPG" ||  $ext == "PNG"  ||  $ext == "JPEG" ){
                    $file2 = file_get_contents($file);
                }else{
                    return response() -> json(config('constants.messages.5.message'), config('constants.messages.5.code'));
                }
            }else{
                return response() -> json(config('constants.messages.6.message'), config('constants.messages.6.code'));
            }

            $match = new Match;

            $faceImageA = new Image;
            $faceImageA->Image = $file1;

            $faceImageB = new Image;
            $faceImageB->Image = $file2;

            $match->faceImageA = $faceImageA;
            $match->faceImageB = $faceImageB;   

            $client = new SoapClient($urlsoap, $configsoap);

            $response = $client->__soapCall("Match", array($match));

            return response() -> json(
                array('data' => $response)
            );

            exit();
            return response() -> json(
                array('data' => base64_encode($response->GetFaceImageResult), 'message' => config('constants.messages.3.message')),
                config('constants.messages.3.code')
            );
            
        }
        catch (SoapFault $e) {
            return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
        }
    }

    function getMatchBetweenTwoImagesService($file1, $file2){
        try {
            // $request = $request->json()->all();

            $ip = "172.20.96.233";
            $port = "8790";
            $user = "system";
            $password = "system";

            $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');

            $configsoap = [
                'login' => $user,
                'password' =>  $password,
                'exceptions' => true,
            ];


            $match = new Match;

            $faceImageA = new Image;
            $faceImageA->Image = $file1;

            $faceImageB = new Image;
            $faceImageB->Image = $file2;

            $match->faceImageA = $faceImageA;
            $match->faceImageB = $faceImageB;   

            $client = new SoapClient($urlsoap, $configsoap);

            $response = $client->__soapCall("Match", array($match));

            return $response->MatchResult->Results->ProbeResultItem->Score;
            
        }
        catch (SoapFault $e) {
            return null;
        }
    }

    public function getFeatureEnrolmentFromPhoto(Request $request){
        try {

            $ip = $request['ip'];
            $port = $request['port'];
            $user = $request['user'];
            $password = $request['pass'];

            $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');

            $configsoap = [
                'login' => $user,
                'password' =>  $password,
                'exceptions' => true,
            ];

            // Global variable for upload file
            $file1;

            // If Upload file photo
            if ($request->file("file1")->isValid()) 
            {
                $file = $request -> file("file1");
                $ext = $file -> getClientOriginalExtension();

                // If file extension is correct
                if( $ext == "jpg" ||  $ext == "png"  ||  $ext == "jpeg" || $ext == "JPG" ||  $ext == "PNG"  ||  $ext == "JPEG" ){
                    $file1 = file_get_contents($file);
                }
                else
                {
                    return response() -> json(config('constants.messages.5.message'), config('constants.messages.5.code'));
                }
            }else{
                return response() -> json(config('constants.messages.6.message'), config('constants.messages.6.code'));
            }

    
         
            $getSubjectEnrolment = new GetSubjectEnrolmentFaceFromPhoto;
            $getSubjectEnrolment->imageBytes = $file1;
 
            $client = new SoapClient($urlsoap, $configsoap);

            
            $response = $client->__soapCall("GetSubjectEnrolmentFaceFromPhoto", array($getSubjectEnrolment));
            
            $feature = $response->GetSubjectEnrolmentFaceFromPhotoResult->Face->Feature;

            return response() -> json(
                array('data' => base64_encode($feature))
            );

            exit();

            return response() -> json(
                array('data' => base64_encode($response->GetFaceImageResult), 'message' => config('constants.messages.3.message')),
                config('constants.messages.3.code')
            );
            
        }
        catch (SoapFault $e) {
            return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
        }
    }

    public function getFeatureEnrolmentFromPhotoService($file1){
        try {

            $ip = "172.20.96.233";
            $port = "8790";
            $user = "system";
            $password = "system";

            $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');

            $configsoap = [
                'login' => $user,
                'password' =>  $password,
                'exceptions' => true,
            ];

            // Global variable for upload file
         
            $getSubjectEnrolment = new GetSubjectEnrolmentFaceFromPhoto;
            $getSubjectEnrolment->imageBytes = $file1;
 
            $client = new SoapClient($urlsoap, $configsoap);

            
            $response = $client->__soapCall("GetSubjectEnrolmentFaceFromPhoto", array($getSubjectEnrolment));
            
            $feature = $response->GetSubjectEnrolmentFaceFromPhotoResult->Face->Feature;

            return $feature;
            
        }
        catch (SoapFault $e) {
            return null;
        }
    }



    public function getSubjectByFeatureByWatchList(Request $request){
        $ip = $request['ip'];
        $port = $request['port'];
        $user = $request['user'];
        $password = $request['pass'];

        $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');

        $configsoap = [
            'login' => $user,
            'password' =>  $password,   
            'exceptions' => true,   
        ];

        $watchListid = $request['watchList'];
        $feature = $request['feature'];


        $matchFeatureByWatchlist = new MatchFeatureByWatchlist;
        $matchFeatureByWatchlist->featureBytes = base64_decode($feature);

        $client = new SoapClient($urlsoap, $configsoap);
 
        $response = $client->__soapCall("MatchFeatureByWatchlist", array($matchFeatureByWatchlist));

        $respuesta = $response->MatchFeatureByWatchlistResult->Results->ProbeResultItem;
        
        return response() -> json(
            array('data' => $respuesta)
        );

        exit();

        return response() -> json(
            array('data' => base64_encode($response->GetFaceImageResult), 'message' => config('constants.messages.3.message')),
            config('constants.messages.3.code')
        );
    }

    
    public function getSubjectByFeatureByWatchListService($feature){

        $ip = "172.20.96.233";
        $port = "8790";
        $user = "system";
        $password = "system";

        $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');

        $configsoap = [
            'login' => $user,
            'password' =>  $password,   
            'exceptions' => true,   
        ];

        $matchFeatureByWatchlist = new MatchFeatureByWatchlist;
        $matchFeatureByWatchlist->featureBytes = $feature;

        $client = new SoapClient($urlsoap, $configsoap);
 
        $response = $client->__soapCall("MatchFeatureByWatchlist", array($matchFeatureByWatchlist));

        $respuesta = $response->MatchFeatureByWatchlistResult->Results->ProbeResultItem->SubjectPhotoId;
        
        return $respuesta;
    }



    public function getImageEnrolSubject(Request $request){
        try {
            
            $ip = $request['ip'];
            $port = $request['port'];
            $user = $request['user'];
            $password = $request['pass'];

            $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');
           
            $configsoap = [
                'login' => $user,
                'password' =>  $password,
                'exceptions' => true,
            ];          
    
            $GetSubjectPhoto = new GetSubjectPhoto;
            $GetSubjectPhoto->subjectPhotoId = $request['photoid'];
 
            $client = new SoapClient($urlsoap, $configsoap);
           
            $response = $client->__soapCall("GetSubjectPhoto", array($GetSubjectPhoto));
    
            $imageEnrol = base64_encode($response->GetSubjectPhotoResult->Image);

            return response() -> json(
                array('data' => $imageEnrol)
            );
        }
        catch (SoapFault $e) {
            return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
        }
    }

    public function getImageEnrolSubjectService($photoId){
        try {
            
            $ip = "190.26.210.53";
            $port = "8790";
            $user = "system";
            $password = "system";

            $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');
           
            $configsoap = [
                'login' => $user,
                'password' =>  $password,
                'exceptions' => true,
            ];          
    
            $GetSubjectPhoto = new GetSubjectPhoto;
            $GetSubjectPhoto->subjectPhotoId = $photoId;
 
            $client = new SoapClient($urlsoap, $configsoap);
           
            $response = $client->__soapCall("GetSubjectPhoto", array($GetSubjectPhoto));
    
            $imageEnrol = $response->GetSubjectPhotoResult->Image;

            return $imageEnrol;
        }
        catch (SoapFault $e) {
            return null;
        }
    }

    public function matching(Request $request){
        try {
           
            // // Global variable for upload file
            $cedula;
            

            // If Upload file photo
            if ($request->file("cedula")->isValid()) 
            {
                $file = $request -> file("cedula");
                $ext = $file -> getClientOriginalExtension();

                // If file extension is correct
                if( $ext == "jpg" ||  $ext == "png"  ||  $ext == "jpeg" || $ext == "JPG" ||  $ext == "PNG"  ||  $ext == "JPEG" ){
                    $cedula = file_get_contents($file);
                }
                else
                {
                    return response() -> json(config('constants.messages.5.message'), config('constants.messages.5.code'));
                }
            }else{
                return response() -> json(config('constants.messages.6.message'), config('constants.messages.6.code'));
            }

            $selfie;

            // If Upload file photo
            if ($request->file("selfie")->isValid()) 
            {
                $file = $request -> file("selfie");
                $ext = $file -> getClientOriginalExtension();
                // If file extension is correct
                if( $ext == "jpg" ||  $ext == "png"  ||  $ext == "jpeg" || $ext == "JPG" ||  $ext == "PNG"  ||  $ext == "JPEG" ){
                    $selfie = file_get_contents($file);
                }else{
                    return response() -> json(config('constants.messages.5.message'), config('constants.messages.5.code'));
                }
            }else{
                return response() -> json(config('constants.messages.6.message'), config('constants.messages.6.code'));
            }

            

            $feature = $this->getFeatureEnrolmentFromPhotoService($cedula);
            $photoId = $this->getSubjectByFeatureByWatchListService($feature);

            $imageEnrol = $this->getImageEnrolSubjectService($photoId);

            $comparacion1 = $this->getMatchBetweenTwoImagesService($cedula,$imageEnrol);
            $comparacion2 = $this->getMatchBetweenTwoImagesService($selfie,$imageEnrol);

            return response() -> json(["Score cedula"=>$comparacion1, "Score Selfie"=>$comparacion2]);

        }
        catch (SoapFault $e) {
            return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
        }

    }

    public function getAuthenticationToken(Request $request){
        try {
            $ip = $request['ip'];
            $port = $request['port'];
            $user = $request['user'];
            $password = $request['pass'];

            $urlsoap = 'http://'.$ip.':'.$port.'/'.config('constants.urlsoap');

            $configsoap = [
                'login' => $user,
                'password' =>  $password,
                'exceptions' => true,
            ];

            $client = new SoapClient($urlsoap, $configsoap);
           
            $response = $client->__soapCall("GetAuthenticationToken", array());

            return response() -> json(["authToken"=>$response->GetAuthenticationTokenResult]);
        }
        catch (SoapFault $e) {
            return response() -> json(config('constants.messages.2.message'), config('constants.messages.2.code'));
        }
    }
}
