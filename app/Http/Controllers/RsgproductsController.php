<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\RsgProduct;
use Illuminate\Support\Facades\Session;
use App\Accounts;
use App\User;
use App\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\MultipleQueue;
use DB;
class RsgproductsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     *
     */

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
		if(!Auth::user()->can(['rsgproducts-show'])) die('Permission denied -- rsgproducts-show');
	
		$teams= DB::select('select bg,bu from asin group by bg,bu ORDER BY BG ASC,BU ASC');

        return view('rsgproducts/index',['teams'=>$teams,'accounts'=>$this->getAccounts(),'users'=>$this->getUsers()]);

    }
	
	public function get(Request $request)
    {
		if(!Auth::user()->can(['rsgproducts-show'])) die('Permission denied -- rsgproducts-show');
		$orderby = $request->input('order.0.column',1);
		if($orderby==2){
			$orderby = 'end_date';
		}elseif($orderby==3){
			$orderby = 'daily_stock';
		}elseif($orderby==4){
			$orderby = 'daily_remain';
		}else{
			$orderby = 'created_at';
		}
        $sort = $request->input('order.0.dir','desc');
        if ($request->input("customActionType") == "group_action") {
			   if(!Auth::user()->can(['rsgproducts-batch-update'])) die('Permission denied -- rsgproducts-batch-update');
			   $updateDate = [];
			   $updateDate['status'] = $request->get("customstatus")?$request->get("customstatus"):0;
			   RsgProduct::whereIn('id',$request->input("id"))->update($updateDate);
        }
		
		$datas= new RsgProduct;
               
        if($request->input('seller_id')){
            $datas = $datas->where('seller_id', $request->input('seller_id'));
        }
		if($request->input('date_from')){
            $datas = $datas->where('start_date','<=', $request->input('date_from'));
        }
		if($request->input('date_to')){
            $datas = $datas->where('end_date','>=',$request->input('date_to'));
        }
		
		if($request->input('bgbu') ){
			   $bgbu = $request->input('bgbu');
			   $bgbu_arr = explode('_',$bgbu);
			   if(count($bgbu_arr)>1){
			   	if(array_get($bgbu_arr,0)) $datas = $datas->where('bg',array_get($bgbu_arr,0));
			   	if(array_get($bgbu_arr,1)) $datas = $datas->where('bu',array_get($bgbu_arr,1));
			   }else{
			   		$datas = $datas->whereNull('bg');
			   }
		}
		if($request->input('user_id')){
            $datas = $datas->where('user_id', $request->input('user_id'));
        }
		if($request->input('asin')){
            $datas = $datas->where('asin','like', $request->input('asin'));
        }
		
		if($request->input('site')){
            $datas = $datas->whereIn('site', $request->input('site'));
        }
		if($request->input('status')!==NULL){
            $datas = $datas->where('status', $request->input('status'));
        }else{
			$datas = $datas->where('status', '>',-1);
		}

		$iTotalRecords = $datas->count();
        $iDisplayLength = intval($_REQUEST['length']);
        $iDisplayLength = $iDisplayLength < 0 ? $iTotalRecords : $iDisplayLength;
        $iDisplayStart = intval($_REQUEST['start']);
        $sEcho = intval($_REQUEST['draw']);
		$lists =  $datas->orderBy($orderby,$sort)->offset($iDisplayStart)->limit($iDisplayLength)->get()->toArray();
        $records = array();
        $records["data"] = array();

        $end = $iDisplayStart + $iDisplayLength;
        $end = $end > $iTotalRecords ? $iTotalRecords : $end;
		$accounts = $this->getAccounts();
		$users= $this->getUsers();
		$pro_req_arr=[];
		$pro_requests = DB::select('select product_id,sum(a) as a,sum(b) as b,sum(c) as c,count(*) as d from 
		(select product_id,(case 
			when step=9 then 1
			else 0
			End) as a,(case 
			when amazon_order_id is null then 0
			else 1
			End) as b,(case 
			when star_rating is null then 0
			else 1
			End) as c from rsg_requests) as f group by product_id');
	
		$pro_requests = json_decode(json_encode($pro_requests), true);
		foreach($pro_requests as $pro_req){
			$pro_req_arr[$pro_req['product_id']]=$pro_req;
		}
		
	
		$status_arr = array('-1'=>'<span class="badge badge-default">Reject</a>',0=>'<span class="badge badge-warning">Pending</a>',1=>'<span class="badge badge-success">Active</span>',2=>'<span class="badge badge-info">Inactive</span>',3=>'<span class="badge badge-danger">Expired</span>');
		foreach ( $lists as $list){
            $records["data"][] = array(
                '<label class="mt-checkbox mt-checkbox-single mt-checkbox-outline"><input name="id[]" type="checkbox" class="checkboxes" value="'.$list['id'].'"/><span></span></label>',
				'<img src="'.array_get($list,'product_img').'" width="50px" height="50px" align="left"/>Account : '.array_get($accounts,$list['seller_id']).'</BR>ASIN :<a href="https://'.array_get($list,'site').'/dp/'.array_get($list,'asin').'?m='.array_get($list,'seller_id').'" target="_blank">'.array_get($list,'asin').'</a></BR>Price : '.round(array_get($list,'price'),2).array_get($list,'currency'),
				$list['start_date'].'</BR>To</BR>'.$list['end_date'],
				$list['daily_stock'],
                $list['daily_remain'],
                $list['review_rating'],
                $list['number_of_reviews'],
				array_get($pro_req_arr,$list['id'].'.d'),
				array_get($pro_req_arr,$list['id'].'.b'),
				array_get($pro_req_arr,$list['id'].'.c'),
				array_get($pro_req_arr,$list['id'].'.a'),
				$list['created_at'],
				array_get($users,$list['user_id']),
				array_get($status_arr,$list['status']),
				
				'<a data-target="#ajax" data-toggle="modal" href="'.url('rsgproducts/'.$list['id'].'/edit').'" class="badge badge-success"> View </a>'
				
            );
		}
        $records["draw"] = $sEcho;
        $records["recordsTotal"] = $iTotalRecords;
        $records["recordsFiltered"] = $iTotalRecords;
        echo json_encode($records);
    }

    public function getUsers(){
        $users = User::get()->toArray();
        $users_array = array();
        foreach($users as $user){
            $users_array[$user['id']] = $user['name'];
        }
        return $users_array;
    }

    public function getAccounts(){
        $seller=[];
		$accounts= DB::connection('order')->table('accounts')->where('status',1)->groupBy(['sellername','sellerid'])->get(['sellername','sellerid']);
		$accounts=json_decode(json_encode($accounts), true);
		foreach($accounts as $account){
			$seller[$account['sellerid']]=$account['sellername'];
		}
		return $seller;
    }

    public function create()
    {
		if(!Auth::user()->can(['rsgproducts-create'])) die('Permission denied -- rsgproducts-create');
        return view('rsgproducts/add',['accounts'=>$this->getAccounts()]);
    }


    public function store(Request $request)
    {
		if(!Auth::user()->can(['rsgproducts-create'])) die('Permission denied -- rsgproducts-create');
        $this->validate($request, [
            'seller_id' => 'required|string',
            'asin' => 'required|string',
			'site' => 'required|string',
			'start_date' => 'required|string',
			'end_date' => 'required|string',
			'daily_stock' => 'required|int',
			'product_name' => 'required|string',
			'product_img' => 'required|string',
			'price' => 'required|string',
			'currency' => 'required|string',
        ]);

        $rule = new RsgProduct;
        $rule->seller_id = $request->get('seller_id');
        $rule->asin = $request->get('asin');
        $rule->site = $request->get('site');
		$rule->start_date = $request->get('start_date');
		$rule->end_date = $request->get('end_date');
		$rule->product_name = $request->get('product_name');
		$rule->product_img = $request->get('product_img');
		$rule->price = round($request->get('price'),2);
		$rule->currency = $request->get('currency');
		$rule->keyword = $request->get('keyword');
		$rule->page = intval($request->get('page'));
		$rule->position = intval($request->get('position'));
		$rule->positive_target = intval($request->get('positive_target'));
		$rule->positive_daily_limit = intval($request->get('positive_daily_limit'));
		
        $rule->status = 0;
		$rule->daily_stock = intval($request->get('daily_stock'));
		$rule->daily_remain = intval($request->get('daily_stock'));
        $rule->user_id = intval(Auth::user()->id);

        $rule->review_rating = intval($request->get('review_rating'));
        $rule->number_of_reviews = intval($request->get('number_of_reviews'));

        if ($rule->save()) {
            $request->session()->flash('success_message','Set Rsg Product Success');
            return redirect('rsgproducts');
        } else {
            $request->session()->flash('error_message','Set Rsg Product Failed');
            return redirect()->back()->withInput();
        }
    }

    public function edit(Request $request,$id)
    {
		if(!Auth::user()->can(['rsgproducts-show'])) die('Permission denied -- rsgproducts-show');
        $rule= RsgProduct::where('id',$id)->first()->toArray();
        if(!$rule){
            $request->session()->flash('error_message','Rsg Product not Exists');
            return redirect('rsgproducts');
        }
        return view('rsgproducts/edit',['rule'=>$rule,'accounts'=>$this->getAccounts()]);
    }

    public function update(Request $request,$id)
    {
		if(!Auth::user()->can(['rsgproducts-update'])) die('Permission denied -- rsgproducts-update');
        $this->validate($request, [
			'start_date' => 'required|string',
			'end_date' => 'required|string',
			'daily_stock' => 'required|int',
			'product_name' => 'required|string',
			'product_img' => 'required|string',
			'price' => 'required|string',
			'currency' => 'required|string',
        ]);
		
        $rule = RsgProduct::findOrFail($id);
		$rule->start_date = $request->get('start_date');
		$rule->end_date = $request->get('end_date');
		$rule->product_name = $request->get('product_name');
		$rule->product_img = $request->get('product_img');
		$rule->price = round($request->get('price'),2);
		$rule->currency = $request->get('currency');
		$rule->keyword = $request->get('keyword');
		$rule->page = intval($request->get('page'));
		$rule->position = intval($request->get('position'));
		$rule->positive_target = intval($request->get('positive_target'));
		$rule->positive_daily_limit = intval($request->get('positive_daily_limit'));
        $rule->status = 0;
		$rule->daily_stock = intval($request->get('daily_stock'));
        //$rule->user_id = intval(Auth::user()->id);
        $rule->review_rating = intval($request->get('review_rating'));
        $rule->number_of_reviews = intval($request->get('number_of_reviews'));
        if ($rule->save()) {
            $request->session()->flash('success_message','Set Rsg Product Success');
            return redirect('rsgproducts');
        } else {
            $request->session()->flash('error_message','Set Rsg Product Failed');
            return redirect()->back()->withInput();
        }
    }


}