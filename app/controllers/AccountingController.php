<?php 
class AccountingController extends BaseController
{
	protected function address($info)
	{
		if($info['entrance']&&$info['apartment'])
			$address = $info['city']." ".$info['street']." ".$info['house']."/".$info['apartment']." כניסה ".$info['entrance'];
		if(!$info['entrance']&&$info['apartment'])
			$address = $info['city']." ".$info['street']." ".$info['house']."/".$info['apartment'];
		if($info['entrance']&&!$info['apartment'])
			$address = $info['city']." ".$info['street']." ".$info['house']." כניסה ".$info['entrance'];
		if(!$info['entrance']&&!$info['apartment'])
			$address = $info['city']." ".$info['street']." ".$info['house'];
		return $address;
	}
	public function orders()
	{
		$key = Input::get('key',0);
		$start = Input::get('from',0);
		$end   = Input::get('to',0);
		if(!$key)
			return Response::json("אנא ספק מזהה.",401);
		$dealsKey = Config::get('dealsApi.dealsKey',0);
		if($dealsKey!=$key)
			return Response::json("אנא ספק מזהה.",401);
		$start 	= date("Y-m-d H:i:s",strtotime($start));
		$end 	= date("Y-m-d H:i:s",strtotime($end));
		$orders = Order::with('items','payment')->whereRaw('createdOn >= ? AND createdOn <= ?',[$start,$end])->orderBy('createdOn','ASC')->get();
		$xml = simplexml_load_file(public_path()."/base.xml");
		$number = 1;
		foreach ($orders as $order) {
			$doc = $xml->addChild('doc');
			$docInfo = $doc->addChild('docinfo');
			$docInfo->addChild('type',320);
			$docInfo->addChild('number',$number);
			$client = $docInfo->addChild('client');
			$client->addChild('firstname',$order->firstName);
			$client->addChild('lastname',$order->lastName);
			$client->addChild('mobile',$order->mobile);
			$client->addChild('address',$this->address($order));
			$number++;
			$date = str_replace('-','',date('Y-m-d',strtotime($order->createdOn)));
			$docInfo->addChild('date',$date);
			$total = 0;
			$docItems = $doc->addChild('items');
			foreach ($order->items as $item) {
				$subject = $docItems->addChild('item');
			 	$total+=$item->qty*$item->priceSingle;
			 	$subject->addChild('code',$item->sku);
			 	$subject->addChild('quantity',$item->qty);
			 	$subject->addChild('price',$item->priceSingle);
			 	$subject->addChild('total',$item->qty*$item->priceSingle);
			 	$subject->addChild('description',$item->description);
			 } 
			 $docPayments = $doc->addChild('payments');
			 foreach ($order->payment as $payment) {
			 	$subject =  $docPayments->addChild('payment');
			 	$subject->addChild('type',$payment['creditDealType']);
			 	$subject->addChild('cardtype',$payment['creditCardType']);
			 	$subject->addChild('cardnumber',substr($payment['cardNumber'],-4));
			 	$subject->addChild('voucher',$payment['voucher']);
			 	$expDate = date('m',strtotime($payment['date']))."/".date('y',strtotime($payment['date']));
			 	$subject->addChild('cardexp',$expDate);
			 	$numberOfPayments = $payment['numberOfPayments']==0 ? 1:$payment['numberOfPayments'];
			 	$subject->addChild('payments',$numberOfPayments);
			 	$subject->addChild('firstpaymentsum',$payment['firstPayment']);
			 	$subject->addChild('additionalpaymentsum',0);
			 	$subject->addChild('total',$payment['total']);
			 	$subject->addChild('ownerid',$payment['ownerId']);
			 }
			$docInfo->addChild('total',$total);
			$docInfo->addChild('orderid',$order->id);
		}			
		return $xml->asXML();
	}
}
