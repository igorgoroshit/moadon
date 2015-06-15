<?php

class SiteOrdersController extends SiteBaseController 
{

	protected function generateKey($length)
    {
        $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return substr(str_shuffle($charset), 0, $length);
    }

	public function index()
	{
		$page  = Input::get('page',1);
		$items = 10;
		$data  = [];
		$count    = $this->client->orders()->count();
		$pages	  = ceil($count/$items);
		$data['meta'] = [
			'page'	=>	$page,
			'count'	=>	$count,
			'pages'	=>	$pages,
		];
		$data['orders'] = $this->client->orders()->join('orders_items','orders_items.orders_id','=','orders.id')
							->select(DB::raw('DATE(createdOn) AS createdOn,orders.id,sum(priceSingle*qty) AS total'))
							->groupBy('orders.id')->forPage($page,$items)->orderBy('orders.id','DESC')->get();
		
    	foreach ($data['orders'] as $order) {
    		$order['createdOn'] = date('d/m/Y',strtotime($order['createdOn']));
    		$order['total'] 	= "₪".number_format($order['total'],2);
    	}
    	if(!count($data['orders']))
    		$data['empty'] = "לא קיימות הזמנות בחשבונך.";
    	return Response::json($data, 200);
	}

	public function show($id)
	{
		$order = $this->client->orders()->with(['items'=>function($q){$q->with('sitedetails');}])->where('id', '=', $id)->first();
		if($order)
		{
			$suppliers = [];
			$total = 0;
			foreach ($order['items'] as &$item) {
				if(!isset($suppliers[$item['suppliers_id']]))
					$suppliers[$item['suppliers_id']] = ['supplierName'=>$item['sitedetails']['supplierName'],'items'=>[]];
				$realized = $item['fullyRealized']==0 ? 'לא מומש':'מומש';
				$itemTotal = "₪".number_format($item['priceSingle']*$item['qty'],2);
				$suppliers[$item['suppliers_id']]['items'][] = ['name'=>$item['name'],'qty'=>$item['qty'],'realized'=>$realized,'total'=>$itemTotal];
	            $total += ($item['qty']*$item['priceSingle']);
	        }
	        $new = [];
	        foreach ($suppliers as $supplier) {
	        	$new[] = $supplier;
	        }
			$order = [
				'id'			=> $order->id,
				'createdOn'		=> date('d/m/Y',strtotime($order->createdOn)),
				'suppliers'			=> $new,
				'total'			=> "₪".number_format($total,2),
			];
		}
		return Response::json($order, 200);
	}
	public function checkout()
	{
		if(!$this->cart)
        	return Response::json('failed', 401);

    $total = $this->cart->items()->sum(DB::raw("price*qty"));
    
    if($total<=0)
    	return Response::json('failed', 401);


    //compute total
    if($this->club->creditDiscount > 0)
    {
    	$creditDiscount = 1 - ($this->club->creditDiscount / 100);
    	$ccTotal = $total / $creditDiscount;
    	$hasCreditDiscount = true;
    }else{
    	$ccTotal = $total;
    	$hasCreditDiscount = false;
    }

    $ccTotal = round($ccTotal, 2);
    
    //do rounding
    $ccTotal = intval($ccTotal);
    $total = intval($total);
    
		$tran = CreditGuardService::startTransaction($ccTotal,$this->client);
		if($tran->status == 0)
		{		
			return Response::json([
				'code' => $tran->code,
				'message' => $tran->message
			], 501);
		}

		//log_items save
		$items = $this->cart->items;
		foreach ($items as $item) {
			$item = $item->toArray();
			$item['gateway_id'] = $tran->id;
			GatewayItem::create($item);
		}
		$url = $tran->url;
		$data = [
			'items'							=> $items,
			'url' 							=> $url,
			'ccTotal'						=> $ccTotal,
			'total'							=> $total,
			'hasCreditDiscount' => $hasCreditDiscount
		];
		return Response::json($data,200);
	}
	

	public function showOrder($key)
	{
		$order = Order::with('items')->where('key','=',$key)->first();
		if(!$order)
			return "הזמנה זו לא נמצאה במערכת.";
		$data = [];
		$data['suppliers'] = [];

		foreach ($order->items as &$item) {
			$supplier = SiteDetails::where('suppliers_id','=',$item->supplier->id)->first();
			$item->supplierName = $supplier->supplierName;
			$data['items'][] = $item;
			if(!isset($data['suppliers'][$supplier->suppliers_id]))
			{
				$city = City::find($supplier->cities_id);
				$supplier->city = $city->name;
				$data['suppliers'][$supplier->suppliers_id] = $supplier;
			}
		}
		$data['orderNum'] = $order->id;
		$data['client']['firstName'] = $order->firstName;
		$data['client']['lastName']  = $order->lastName;
		return View::make('mail.order',$data);
	}

}

