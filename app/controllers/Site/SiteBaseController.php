<?php

class SiteBaseController extends BaseController 
{

	public function callAction($method,$parameters)
	{
		$subdomain = array_shift($parameters);

		if(empty($subdomain) || $subdomain == 'www')
			$subdomain = 'cpnclub';

		$club = Club::where('urlName','=',$subdomain)->first();
		if(!$club)
			return "מועדון זה לא קיים $subdomain";
		$this->club = $club;
		
		return parent::callAction($method, $parameters);
	}


	public function __construct(Igorgoroshit\TokenAuth\TokenAuth $auth)
	{
		$this->auth = $auth;
		if($this->_setPayload())
		{
				$this->_setClient();
			$this->_setCart();
		}

	}

	protected function _setPayload()
	{
		try{
			$token = $this->auth->getToken();
			if(!$token)
			{
				return false;
			}
			$token = $token->get();
			$this->payload = $this->auth->getPayload($token)->toArray();
		}catch(Exception $e)
		{
			return false;
		}

		return true;
	}

	protected function _setClient()
	{
		$client = Client::find($this->payload['user']);
		if($client)
			$this->client = $client;
		else
			$this->client = null;
	}

	protected function _setCart()
	{
		
		$cart = Cart::find($this->payload['cart_id']);
		//print_r($this->payload); die($cart->id);
		if($cart)
			$this->cart = $cart;
		else
			$this->cart = null;
	}

	protected function _getCart($carts_id)
	{
		$cart = CartItem::where('carts_id','=',$carts_id)->join('items','items.id','=','items_id')
						->join('sitedetails','items.suppliers_id','=','sitedetails.suppliers_id')
						->select(DB::raw('supplierName,priceSingle,items.name,carts_items.qty AS count,items.priceSingle,carts_items.items_id AS id,items.description,carts_items.carts_id'))
						->groupBy('items_id')->get();
		return $cart;
	}
}