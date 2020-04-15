
<?php
class Application_Model_Smsapi extends Muzyka_DataModel
{

    public function save($data)
    {

    }

    public function sendsms($data)
    {
    /*	$req = $this->getRequest();
        $reportType = $req->getParam('type');*/

        $temp1 = "Thank you for contacting Kryptos here is your tracking ID : #".$data['tid'].", You can check status of your ticket at http://localhost/webform/trackstatus/tid/".$data['tid']; // new ticket

        $temp2 = "Your ticket has been updated. Here is your tracking ID : #".$data['tid'].", You can check status of your ticket at http://localhost/webform/trackstatus/tid/".$data['tid']; //update ticket
        $temp3 = "Thank you ! Your ticket has been closed. Here is your tracking ID : #".$data['tid'].", You can check status of your ticket at http://localhost/webform/trackstatus/tid/".$data['tid'];  //closed ticket

        switch ($data['type']) {
        	case 1:
        		$msg = $temp1;
        		break;

    		case 2:
	    		$msg = $temp2;
	    		break;

    		case 3:
	    		$msg = $temp3;
	    		break;

        	default:
        		$msg = "Error! Messgae type not found";
        		break;
        }

		$mobile = $data['mobile'];

		$params = array(
	    'access_token'  => 'ZUxxqXctw7A84H2PSYQShLCB3zmANki5wwZHeLyB',          //sms api access token
	    'to'            => $mobile,         	  								//destination number  
	    'from'          => 'Info',                								//sender name has to be active  
	    'message'       => $msg,    			  								//message content
		);
	
		if ($params['access_token']&&$params['to']&&$params['message']&&$params['from']) {
		    $date = '?'.http_build_query($params);
		    $file = fopen('https://api.smsapi.com/sms.do'.$date,'r');
		    $result = fread($file,1024);
		    fclose($file);
		    if($result){
				echo 'Send Message Successfully Here is Sender Id : ';	
				echo $result;
			}else{
				echo 'Send Fail';
			}
		}
	return true;

    }
}


?>