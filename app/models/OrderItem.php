<?php

class OrderItem extends Eloquent {

	protected $table = 'orders_items';
	protected $fillable = array('name','description','expirationDate','listPrice','clubPrice','netPrice','items_id','suppliers_id','qty',
					'shortDescription','sku','notes','listPriceGroup','netPriceGroup','minParticipants','maxParticipants','itemtypes_id',
					'orders_id','priceSingle','priceGroup',"noCreditDiscountPrice","noDiscountPrice"
					);
	public $timestamps = false;

	public function realized()
	{
		return $this->hasMany('Realized','orders_items_id','id');
	}

	public function supplier()
	{
		return $this->belongsTo('Supplier','suppliers_id','id');
	}
	public function sitedetails()
	{
		return $this->belongsTo('SiteDetails','suppliers_id','suppliers_id');
	}
	public function order()
	{
		return $this->belongsTo('Order','orders_id','id');
	}
}

