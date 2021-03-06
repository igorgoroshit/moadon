<?php

class AdminReportsController extends BaseController
{
	public function suppliersReport()
	{
		$startDate 	= Input::get('startDate',0);
		$endDate   	= Input::get('endDate',0);
		if($startDate)
			$startDate = date('Y-m-d',strtotime(str_replace('/','-',$startDate)));
		if($endDate)
			$endDate = date('Y-m-d',strtotime(str_replace('/','-',$endDate)));
		if(!$startDate&&!$endDate)
			return Response::json(['reports'=>[]],201);
			
		$query1 = "(SELECT  suppliers.name            AS supplierName,
					        suppliers.id              AS supplierId,
					        count(DISTINCT orders.id) AS ordersTotalNum,
					        sum(IF(orders_statuses_id!=4,noCreditDiscountPrice * qty,0))    AS ordersPayedTotal,
					        sum(IF(orders_statuses_id!=4,pricesingle * qty,0)) AS priceSingleTotal,
					        sum(IF(orders_statuses_id!=4,netprice * qty,0)) AS ordersNetTotal,
					        sum(IF(orders_statuses_id=4,1,0)) AS ordersCanceled,
					        sum(IF(orders_statuses_id=4,qty,0)) AS ordersCanceledQty,
					        sum(IF(orders_statuses_id=4,noCreditDiscountPrice * qty,0)) AS ordersCanceledTotal,
					        sum(IF(orders_statuses_id=4,pricesingle * qty,0)) AS ordersCanceledTotalSingle,
					        sum(IF(orders_statuses_id=4,netprice * qty,0)) AS ordersCanceledNet,
					        sum(IF(orders_statuses_id!=4,qty,0)) AS ordersTotalQty
					 FROM   orders
					        INNER JOIN orders_items
					                ON orders.id = orders_id
					        INNER JOIN suppliers
					                ON suppliers.id = orders_items.suppliers_id
					 WHERE  date(createdOn) >= ?
					        AND date(createdOn) <= ? group by supplierId)";
		$query2 = "(SELECT   suppliers.name            AS supplierName,
								count(DISTINCT orders_id) AS realizations,
								sum(realizedQty) as realizedNum,
					           suppliers.id       AS supplierId,
					           sum(noCreditDiscountPrice*qty) AS realizedPayedTotal,
					           sum(pricesingle*qty) AS priceSingleRealizedTotal,
					           sum(netprice*qty) AS realizedNetTotal 
					           FROM items_realizations
					           INNER JOIN orders_items
					           ON         orders_items.id = orders_items_id
					           INNER JOIN suppliers
					           ON         suppliers.id = orders_items.suppliers_id WHERE date(realizedOn) >= ?
					           AND        date(realizedOn) <= ? group by supplierId)";
		$bindings = [$startDate,$endDate];
		$data = [];
		$temp = [];
		$orders = DB::select($query1,$bindings);
		$realized = DB::select($query2,$bindings);
		$formatNumbers = ['realizations','realizedNum','realizedPayedTotal','priceSingleRealizedTotal',
			'realizedNetTotal','ordersTotalNum','ordersPayedTotal','priceSingleTotal','ordersNetTotal','ordersCanceled',
			'ordersCanceledQty','ordersTotalQty','ordersCanceledTotalSingle','ordersCanceledTotal','ordersCanceledNet'];
		foreach ($orders as $order) {
			$order = get_object_vars($order);
			$temp[$order['supplierId']] = $order;
		}
		foreach ($realized as &$realizedItem) {
			$realizedItem = get_object_vars($realizedItem);
			if(isset($temp[$realizedItem['supplierId']]))
				$temp[$realizedItem['supplierId']] = array_merge($temp[$realizedItem['supplierId']],$realizedItem);
			else
				$temp[$realizedItem['supplierId']] = $realizedItem;
			
		}

		$new = [];
		$totals = [
			'supplierName' => 'סיכום',
			'ordersPayedTotal'=>0,
			'ordersNetTotal'=>0,
			"priceSingleTotal"=>0,
			"priceSingleRealizedTotal"=>0,
			'realizedPayedTotal'=>0,
			'realizedNetTotal'=>0,
			'ordersCanceledTotalSingle'=>0,
			'ordersCanceledTotal'=>0,
			'ordersCanceledNet'=>0,
			'realizations'=>0,
			'realizedNum'=>0,
			'ordersNum'=>0,
			'ordersTotalQty'=>0,
			'ordersCanceled'=>0,
			'ordersCanceledQty'=>0,
		];
		foreach ($temp as &$line) {

			
			$line['ordersCanceled'] = Order::whereHas('items',function($q) use($line){
				$q->where('suppliers_id','=',$line['supplierId']);
			})->where('orders_statuses_id','=',4)->whereRaw('date(createdOn) >= ? && date(createdOn) <= ?',[$startDate,$endDate])->count();
			$line['ordersTotalNum'] = isset($line['ordersTotalNum']) ? $line['ordersTotalNum']:0;
			$line['ordersCanceledQty'] = isset($line['ordersCanceledQty']) ? $line['ordersCanceledQty']:0;
			$line['ordersNum'] = intval($line['ordersTotalNum'])-intval($line['ordersCanceled']);
			foreach ($line as $key => &$value) {
				
				if(is_numeric($value)&&isset($totals[$key]))
					$totals[$key] = $totals[$key]+$value;
				if(is_numeric($value)&&in_array($key,$formatNumbers))
				{
					$value = floatval($value);
					$afterDot = floor( $value ) != $value ?2:0;
					$value = number_format($value,$afterDot);
				}
			}
			$new[] = array_merge(['realizations'=>0,'ordersNum'=>0,'ordersPayedTotal'=>0,
				'ordersNetTotal'=>0,"priceSingleTotal"=>0,"priceSingleRealizedTotal"=>0,
				'realizedNum'=>0,'realizedPayedTotal'=>0,'realizedNetTotal'=>0,'supplierName'=>""],$line);
		}

		if(count($temp))
		{
			foreach ($totals as $key => &$value) {
				
				if(is_numeric($value))
				{
					$value = floatval($value);
					$afterDot = floor( $value ) != $value ?2:0;
					$value = number_format($value,$afterDot);
				}
			}
			$new[] = $totals;
		}
		$data['reports'] = $new;
		$data['realized'] = $realized;
		$data['orders'] = $orders;
		return Response::json($data,201);
	}
}