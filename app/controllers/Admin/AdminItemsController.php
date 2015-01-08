<?php

class AdminItemsController extends BaseController 
{
	protected function rules($itemtypes_id)
	{
		$rules = array(
			'name' 				=>'required',
			'description'		=>'required',
			'expirationDate'	=>'required',
			'states_id'			=>'required',
			'sku'				=>'required',
			'suppliers_id'		=>'required',
		);
		if($itemtypes_id == 1||$itemtypes_id == 3)
		{
			$rules['listPrice'] = 'required|numeric|min:1';
			$rules['netPrice']  = 'required|numeric|min:1';
			$rules['clubPrice'] = 'required|numeric|min:1';
		}
		if($itemtypes_id == 2||$itemtypes_id == 3)
		{
			$rules['listPriceGroup'] 	= 'required|numeric|min:1';
			$rules['netPriceGroup'] 	= 'required|numeric|min:1';
			$rules['minParticipants'] 	= 'required|numeric|min:1';
			$rules['maxParticipants'] 	= 'required|numeric|min:1';	
		}
		return $rules;
	}

	public function create()
	{
		$temp = $item = array();
		$temp['main'] = array('images'=>array());
		$temp['main']['base'] = URL::to('/')."/galleries/";
		$item['galleries'] = $temp;
		$item['uploadUrl'] = '/uploadImage';
		return Response::json($item,200);
	}
	public function index()
	{
		$items = Input::get('items',10);
		$page  = Input::get('page',1);
		$query = Input::get('query',0);
		$sql = $query ? "name LIKE CONCAT('%',?,'%')" :'? = 0';
		$count = Item::whereRaw($sql,array($query))->count();
		$pages = ceil($count/$items);
		$item = Item::with('supplier')->whereRaw($sql,array($query))->skip($page*$items-$items)->take($items)->get();
		$item = $item->toArray();
		$meta = array(
			'pages' => $pages,
			'count' => $count,
			'page'	=> $page
			);
		$data = array('collection'=>$item,'meta'=>$meta);
		return Response::json($data,200);
	}

	public function store()
	{
		$json   = Request::getContent();
    	$data   = json_decode($json,true);
    	if(!isset($data['itemtypes_id'])||!ItemType::where('id','=',$data['itemtypes_id'])->count())
    		return Response::json(array('error'=>"סוג מוצר זה לא נמצא במערכת"),501);
    	$validator = Validator::make($data, $this->rules($data['itemtypes_id']));

    	if($validator->fails())
    		return Response::json(array('error'=>"אנא וודא שסיפקתה את כל הנתונים הדרושים"),501);
    	$data['expirationDate'] = implode('-',array_reverse(explode('/',$data['expirationDate'])));
    	if(!Supplier::where('id','=',$data['suppliers_id'])->count())
    		return Response::json(array('error'=>"ספק זה לא נמצא במערכת"),501);
    	if(Item::where('sku','=',$data['sku'])->where('suppliers_id','=',$data['suppliers_id'])->count())
    		return Response::json(array('error'=>'מוצר אם מק"ט זה כבר קיים במערכת במערכת'),501);
    	if(Item::where('name','=',$data['name'])->where('suppliers_id','=',$data['suppliers_id'])->count())
    		return Response::json(array('error'=>'מוצר אם שם זה כבר קיים במערכת במערכת'),501);
    	$item 	= new Item;
    	$item 	= $item->create($data);
    	$ids = array();
    	if(isset($data['galleries']))
    	{
    		foreach ($data['galleries'] as $gallery) {
    			$newGallery = new Gallery;
				$newGallery = $newGallery->create(array('type'=>'main'));
				$gallery['id'] = $newGallery->id;
				$ids[] = $newGallery->id;
    			App::make('AdminImagesController')->galleriesImages($gallery['images'],$gallery['id']);
    		}
    	}	
    	if(count($ids))
    		$item->galleries()->attach($ids);
    	return Response::json(json_decode($this->show($item->id)->getContent(),true),201);
	}

	public function show($id)
	{
		$item = Item::with('galleries')->find($id);
		if(!$item)
			return Response::json(array('error'=>'מוצר זה לא נמצא במערכת'),501);
		$item = $item->toArray();
		$temp = array();
		$temp['main'] = isset($item['galleries'][0]) ? $item['galleries'][0]:array('images'=>array());
		$temp['main']['base'] = URL::to('/')."/galleries/";
		$item['galleries'] = $temp;
		$item['uploadUrl'] = '/uploadImage';
		$item['expirationDate'] = implode('/',array_reverse(explode('-',$item['expirationDate'])));
		return Response::json($item,200);
	}

	public function update($id)
	{
		$json=Request::getContent();
	    $data=json_decode($json,true);
		$item = Item::find($id);
		if(!$item)
			return Response::json(array('error'=>'מוצר זה לא נמצא במערכת'),501);
		if(!isset($data['itemtypes_id'])||!ItemType::where('id','=',$data['itemtypes_id'])->count())
    		return Response::json(array('error'=>"סוג מוצר זה לא נמצא במערכת"),501);
		$validator = Validator::make($data,$this->rules($data['itemtypes_id']));
    	if($validator->fails())
    		return Response::json(array('error'=>"אנא וודא שסיפקתה את כל הנתונים הדרושים"),501);
    	if(!Supplier::where('id','=',$data['suppliers_id'])->count())
    		return Response::json(array('error'=>"ספק זה לא נמצא במערכת"),501);
    	if(Item::where('sku','=',$data['sku'])->where('suppliers_id','=',$data['suppliers_id'])->where('id','!=',$id)->count())
    		return Response::json(array('error'=>'מוצר אם מק"ט זה כבר קיים במערכת במערכת'),501);
    	if(Item::where('name','=',$data['name'])->where('suppliers_id','=',$data['suppliers_id'])->where('id','!=',$id)->count())
    		return Response::json(array('error'=>'מוצר אם שם זה כבר קיים במערכת במערכת'),501);
    	if(isset($data['galleries']))
    	{
    		$galleryTypes = array('main');
			$existingTypes = $item->galleries()->lists('type');
			$missingTypes = array_diff($galleryTypes,$existingTypes);
			$ids = array();
			foreach ($data['galleries'] as $gallery) 
			{
				if(count($missingTypes)||!isset($gallery['id']))
				{
					$newGallery = new Gallery;
					$newGallery = $newGallery->create(array('type'=>current($missingTypes)));
					unset($missingTypes[array_search(current($missingTypes),$missingTypes)]);
					$gallery['id'] = $newGallery->id;
					$ids[] = $newGallery->id;
				}
				App::make('AdminImagesController')->galleriesImages($gallery['images'],$gallery['id']);
    		}
    	}	
    	$data['expirationDate'] = implode('-',array_reverse(explode('/',$data['expirationDate'])));
    	if(count($ids))
    		$item->galleries()->attach($ids);
    	$item = $item->fill($data);
    	$item->save();
    	return Response::json(json_decode($this->show($item->id)->getContent(),true),201);
	}

	public function destroy($id)
	{
		$item = Item::find($id);
		if(!$item)
			return Response::json(array('error'=>'מוצר זה לא נמצא במערכת'),501);
		$item->delete();
	}
}