<?php
namespace BrandDisOrder\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;


/**
 * Class ContentController
 * @package StockUpdatePlugin\Controllers
 */
class ContentController extends Controller
{
	/**
	 * @param Twig $twig
	 * @return string
	 */
	private $shouldReturn;
	public function runOrder() {
		$order_id = $_GET['order_id'];
		$this->shouldReturn = "yes";
		$this->testOrder($order_id);
	}
	public function testOrder($order_id) {
		$orders = $this->main_order($order_id);
        $orders = json_decode($orders, TRUE);
        echo json_encode($orders);
        echo "<br><br>---------------------------";
        $orderItemsData = $this->order($order_id);
        $orderItemsData = json_decode($orderItemsData, TRUE);
        echo json_encode($orderItemsData);
	}
	public function saveOrderMap($order_id) {

		$orders = $this->main_order($order_id);
        $orders = json_decode($orders, TRUE);
		if($orders['statusId'] != "5") exit;
		if($orders['relations'][0]['referenceId'] != "104") exit;
		/*
		if(isset($orders['properties'])) {
			foreach($orders['properties'] as $property) {
				if($property['typeId'] == "7" && is_numeric($property['value'])) {
					exit;
				}
			}
		}
		*/
        $orderItemsData = $this->order($order_id);
        $orderItemsData = json_decode($orderItemsData, TRUE);
		if(empty($orderItemsData)) exit;

        $operationData = array();
        $OrderProducts = array();
        foreach ($orderItemsData['entries'] as  $value) {
			if(empty($value['itemVariationId'])) continue;

            $getVariation = $this->getVariation($value['itemVariationId']);
            $getVariation = json_decode($getVariation, TRUE);

            $stock_id = $getVariation['entries'][0]['number'];
            if(empty($stock_id)) continue;
            $item_id = $getVariation['entries'][0]['itemId'];
            //$flag = $this->isFlagThree($item_id);
            //if($flag == 0) continue;

            $qty = $value['quantity'];
			$operationData[] = array(
               "stock_id"=>"$stock_id", "qty"=>"$qty");
            $OrderProducts[] = array('modelId'=>"$stock_id", 'qty'=>"$qty");

        }
		if(empty($OrderProducts)) exit;

        //$reserveOrder = $this->reserve($operationData);

       // $lockedOrder = $this->lockedOrder();
       // $acquireOrder = $this->acquireOrder($OrderProducts);

        //echo json_encode($acquireOrder)
        $customerDetail = $this->customerDetail($order_id);
        $customerDetail['order_number'] = $order_id;
        $acquireOrder = $order_id;
        $SingleRecipientOrder = $this->SingleRecipientOrder($customerDetail, $OrderProducts);
        /*
        foreach ($orderItemsData['entries'] as  $value) {

            $getVariation = $this->getVariation($value['itemVariationId']);
            $getVariation = json_decode($getVariation, TRUE);

            $stock_id = $getVariation['entries'][0]['number'];
            $qty = $value['quantity'];
            $SingleRecipientOrder = $this->SingleRecipientOrder($customerDetail, $stock_id, $qty);

        }
		*/
        if (!empty($acquireOrder) && is_numeric($acquireOrder)) {
          $UpdateStatus = $this->UpdateStatus($order_id);
          //$OrderFlagProperty = $this->OrderFlagProperty($order_id, $acquireOrder);
        }
        if($this->shouldReturn == "yes") {
			echo json_encode($acquireOrder);
		}
	}
	public function main_order($orderId){
		$login = $this->login();
		$login = json_decode($login, true);
		$access_token = $login['access_token'];
		//$host = $_SERVER['HTTP_HOST'];
		$host = "joiurjeuiklb.plentymarkets-cloud02.com";
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://".$host."/rest/orders/".$orderId,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 90000,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "authorization: Bearer ".$access_token,
            "cache-control: no-cache",
            "postman-token: 77b15284-d14b-3b3f-c085-904253595e91"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return "cURL Error #:" . $err;
        } else {
			//echo $response;
          return $response;
        }
    }


    public function reserve($operationData){
        $operationLock = '<operation type="lock">';
        foreach ($operationData as $value) {
            $operationLock .= ' <model stock_id="'.$value['stock_id'].'" quantity="'.$value['qty'].'" />';
        }
        $operationLock .= '</operation>';
        /*
        $operationUnlock = '<operation type="unlock">';
        foreach ($operationData['unlock'] as $value) {
            $operationUnlock .= ' <model stock_id="'.$value['stock_id'].'" quantity="'.$value['qty'].'" />';
        }
        $operationUnlock .= '</operation>';
        $operationSet = '<operation type="set">';
        foreach ($operationData['set'] as $value) {
            $operationSet .= ' <model stock_id="'.$value['stock_id'].'" quantity="'.$value['qty'].'" />';
        }
        $operationSet .= '</operation>';
		*/
        $requestData = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <root>'.$operationLock.'</root>';

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://www.brandsdistribution.com/restful/ghost/orders/sold",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 900000,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $requestData,
          CURLOPT_HTTPHEADER => array(
            "accept: application/xml",
            "authorization: Basic MTg0Y2U4Y2YtMmM5ZC00ZGU4LWI0YjEtMmZkNjcxM2RmOGNkOlN1cmZlcjc2",
            "cache-control: no-cache",
            "content-type: application/xml"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "cURL Error #:" . $err;
        } else {
            $xml = simplexml_load_string($response);
            $json = json_encode($xml);
            $arrayData = json_decode($json,TRUE);
          return $arrayData;
        }
    }

    public function lockedOrder(){

        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://www.brandsdistribution.com/restful/ghost/orders/dropshipping/locked/",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 900000,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "accept: application/xml",
            "authorization: Basic MTg0Y2U4Y2YtMmM5ZC00ZGU4LWI0YjEtMmZkNjcxM2RmOGNkOlN1cmZlcjc2",
            "cache-control: no-cache",
            "content-type: application/xml"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return "cURL Error #:" . $err;
        } else {
          $xml = simplexml_load_string($response);
            $json = json_encode($xml);
            $arrayData = json_decode($json,TRUE);
          return $arrayData;
        }
    }
    public function acquireOrder($productArray){
        $productTag = "";
        foreach ($productArray as  $value) {
             $productTag .= ' <product stock_id="'.$value['modelId'].'" quantity="'.$value['qty'].'" />';
        }
        $requestData = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><supplierorder><products>'.$productTag.'</products></supplierorder>';
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://www.brandsdistribution.com/restful/ghost/supplierorder/acquire",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 9000000,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $requestData,
          CURLOPT_HTTPHEADER => array(
            "authorization: Basic MTg0Y2U4Y2YtMmM5ZC00ZGU4LWI0YjEtMmZkNjcxM2RmOGNkOlN1cmZlcjc2",
            "cache-control: no-cache",
            "content-type: application/xml"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return "cURL Error #:" . $err;
        } else {
          return $response;
        }
    }

    public function SingleRecipientOrder($recipientData, $OrderProducts){

        $products = "";
        foreach($OrderProducts as $product) {
			$products .= "<item><stock_id>".$product['modelId']."</stock_id><quantity>".$product['qty']."</quantity></item>";
		}

        $requestData = "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<root>
    <order_list>
        <order>
            <key>".$recipientData['order_number']."</key>
            <date>".$recipientData['date']."</date>
            <recipient_details>
                <recipient>".$recipientData['recipient']."</recipient>
                <careof />
                <cfpiva></cfpiva>
                <customer_key></customer_key>
                <notes></notes>
                <address>
                    <street_type></street_type>
                    <street_name>".$recipientData['street_name']."</street_name>
                    <address_number>".$recipientData['address_number']."</address_number>
                    <zip>".$recipientData['zip']."</zip>
                    <city>".$recipientData['city']."</city>
                    <province></province>
                    <countrycode>".$recipientData['countrycode']."</countrycode>
                </address>
                <phone>
                    <prefix>".$recipientData['prefix']."</prefix>
                    <number>".$recipientData['number']."</number>
                </phone>
            </recipient_details>
            <item_list>".$products."
            </item_list>
        </order>
    </order_list>
</root>";
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://www.brandsdistribution.com/restful/ghost/orders/0/dropshipping",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 9000000,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $requestData,
          CURLOPT_HTTPHEADER => array(
            "accept: application/xml",
            "authorization: Basic MTg0Y2U4Y2YtMmM5ZC00ZGU4LWI0YjEtMmZkNjcxM2RmOGNkOlN1cmZlcjc2",
            "cache-control: no-cache",
            "content-type: application/xml"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return "cURL Error #:" . $err;
        } else {
          $xml = simplexml_load_string($response);
            $json = json_encode($xml);
            $arrayData = json_decode($json,TRUE);
          return $arrayData;
        }
    }
    public function order($orderId){
        $login = $this->login();
        $login = json_decode($login, true);
        $access_token = $login['access_token'];
        //$host = $_SERVER['HTTP_HOST'];
		$host = "joiurjeuiklb.plentymarkets-cloud02.com";

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://".$host."/rest/orders/".$orderId."/items",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "authorization: Bearer ".$access_token,
            "cache-control: no-cache",
            "postman-token: 77b15284-d14b-3b3f-c085-904253595e91"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return "cURL Error #:" . $err;
        } else {
          return $response;
        }
    }
    public function login(){
        //$host = $_SERVER['HTTP_HOST'];
        $host = "joiurjeuiklb.plentymarkets-cloud02.com";
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://".$host."/rest/login",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          //CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "username=API-USER&password=%5BnWu%3Bx%3E8Eny%3BbSs%40",
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/x-www-form-urlencoded",
            "postman-token: 49a8d541-073c-8569-b3c3-76319f67e552"
          ),
          CURLOPT_TIMEOUT=> 90000000
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return "cURL Error #:" . $err;
        } else {
          return $response;
        }
    }
    public function getVariation($id){
        $login = $this->login();
        $login = json_decode($login, true);
        $access_token = $login['access_token'];
        //$host = $_SERVER['HTTP_HOST'];
		$host = "joiurjeuiklb.plentymarkets-cloud02.com";
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://".$host."/rest/items/variations?id=".$id,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 90000,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "authorization: Bearer ".$access_token,
            "cache-control: no-cache"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return "cURL Error #:" . $err;
        } else {
          return $response;
        }
    }
    public function customerDetail($orderId){
        $login = $this->login();
        $login = json_decode($login, true);
        $access_token = $login['access_token'];
        //$host = $_SERVER['HTTP_HOST'];
		$host = "joiurjeuiklb.plentymarkets-cloud02.com";
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://".$host."/rest/orders/".$orderId."?with[]=addresses&with[]=relation",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 90000000,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "authorization: Bearer ".$access_token,
            "cache-control: no-cache",
            "postman-token: 416bc02e-dffa-1fb1-b443-9fa00dc4c675"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return "cURL Error #:" . $err;
        } else {
            $response = json_decode($response, TRUE);
            $detailArray = array();
            $detailArray['date'] = date('Y/m/d h:i:s')." +0000";
            $recipient = ""; $street_name = ""; $address_number = ""; $zip = ""; $city = "";
            if(isset($response['addresses'][0])) {
				$recipient = $response['addresses'][0]['name3']." ".$response['addresses'][0]['name2'];
				$street_name = $response['addresses'][0]['address2'].", ".$response['addresses'][0]['address1'].", ".$response['addresses'][0]['address3'];
				$address_number = $response['addresses'][0]['address2'];
				$zip = $response['addresses'][0]['postalCode'];
				$city = $response['addresses'][0]['town'];
			}
			if(isset($response['addresses'][1])) {
				$recipient = $response['addresses'][1]['name3']." ".$response['addresses'][1]['name2'];
				$street_name = $response['addresses'][1]['address2'].", ".$response['addresses'][1]['address1'].", ".$response['addresses'][1]['address3'];
				$address_number = $response['addresses'][1]['address2'];
				$zip = $response['addresses'][1]['postalCode'];
				$city = $response['addresses'][1]['town'];
			}

            $detailArray['date'] = date('Y/m/d h:i:s')." +0000";
            $detailArray['recipient'] = $recipient;
            $detailArray['street_name'] = $street_name;
            $detailArray['address_number'] = $address_number;
            $detailArray['zip'] = $zip;
            $detailArray['city'] = $city;
            $countryId = $response['addresses'][0]['countryId'];
            $countryCode = $this->getCountryCode($countryId);
            $detailArray['countrycode'] = $countryCode;
            $prefix = (explode(" ",$response['relations'][1]['contactReceiver']['privatePhone']));
            $detailArray['prefix'] = $prefix[0];
            $number = '';
            foreach ($prefix as $value) {
                if ($value != $prefix[0]) {
                    $number .= $value;
                }
            }
            $detailArray['number'] = $number;

          return $detailArray;
        }
    }

    public function getCountryCode($countryId){
        $login = $this->login();
        $login = json_decode($login, true);
        $access_token = $login['access_token'];
        //$host = $_SERVER['HTTP_HOST'];
		$host = "joiurjeuiklb.plentymarkets-cloud02.com";
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://".$host."/rest/orders/shipping/countries",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 900000,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "authorization: Bearer ".$access_token,
            "cache-control: no-cache"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return "cURL Error #:" . $err;
        } else {
            $response = json_decode($response, TRUE);
            foreach ($response as $value) {
                if ($value['id'] == "$countryId") {
                    $result = $value['isoCode2'];
                    break;
                }
            }
          return $result;
        }
    }

    public function orderStatusOrderId($orderNumber){

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://www.brandsdistribution.com/restful/ghost/clientorders/serverkey/".$orderNumber,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 9000000,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "authorization: Basic MTg0Y2U4Y2YtMmM5ZC00ZGU4LWI0YjEtMmZkNjcxM2RmOGNkOlN1cmZlcjc2",
            "cache-control: no-cache"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return "cURL Error #:" . $err;
        } else {
          $xml = simplexml_load_string($response);
            $json = json_encode($xml);
            $arrayData = json_decode($json,TRUE);
          return $arrayData;
        }
    }


    public function UpdateStatus($orderId){
    $login = $this->login();
    $login = json_decode($login, true);
    $access_token = $login['access_token'];
    //$host = $_SERVER['HTTP_HOST'];
    $host = "joiurjeuiklb.plentymarkets-cloud02.com";
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://".$host."/rest/orders/".$orderId,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 90000,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "PUT",
      CURLOPT_POSTFIELDS => "{\n\t\"plentyId\": 42296,\n\t\"statusId\":6\n}",
      CURLOPT_HTTPHEADER => array(
        "authorization: Bearer ".$access_token,
        "cache-control: no-cache",
        "content-type: application/json"
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      return "cURL Error #:" . $err;
    } else {
      return $response;
    }
  }

    public function OrderFlagProperty($orderId, $flagValue){

        $login = $this->login();
        $login = json_decode($login, true);
        $access_token = $login['access_token'];
       // $host = $_SERVER['HTTP_HOST'];
		$host = "joiurjeuiklb.plentymarkets-cloud02.com";
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://".$host."/rest/orders/".$orderId."/properties",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 90000,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "{\n\t\"orderId\": $orderId,\n\t\"typeId\": 7,\n\t\"value\": \"$flagValue\"\n}",
          CURLOPT_HTTPHEADER => array(
            "authorization: Bearer ".$access_token,
            "cache-control: no-cache",
            "content-type: application/json"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return "cURL Error #:" . $err;
        } else {
          return $response;
        }
    }

    public function isFlagThree($id){
        $login = $this->login();
        $login = json_decode($login, true);
        $access_token = $login['access_token'];
        //$host = $_SERVER['HTTP_HOST'];
		$host = "joiurjeuiklb.plentymarkets-cloud02.com";
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://".$host."/rest/items/".$id,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 90000,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "authorization: Bearer ".$access_token,
            "cache-control: no-cache"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return "cURL Error #:" . $err;
        } else {
          $response = json_decode($response, TRUE);
          if(isset($response['flagTwo']) && ($response['flagTwo'] == "3" || $response['flagTwo'] == "1")) {
			return "1";
		  }
		  else {
			return "0";
		  }
        }
    }

}
