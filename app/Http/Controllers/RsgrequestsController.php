<?php

namespace App\Http\Controllers;

use \DrewM\MailChimp\MailChimp;
use Illuminate\Http\Request;
use App\RsgRequest;
use App\RsgProduct;
use Illuminate\Support\Facades\Session;
use App\Accounts;
use App\User;
use App\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\MultipleQueue;
use DB;
use PayPal\PayPalAPI\TransactionSearchReq;
use PayPal\PayPalAPI\TransactionSearchRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RsgrequestsController extends Controller
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

	
		$date_from=date('Y-m-d',strtotime('-90 days'));		
		$date_to=date('Y-m-d');

		$submit_date_from=date('Y-m-d',strtotime('-90 days'));
		$submit_date_to=date('Y-m-d');

		return view('rsgrequests/index',['date_from'=>$date_from ,'date_to'=>$date_to ,'submit_date_from'=>$submit_date_from ,'submit_date_to'=>$submit_date_to,'users'=>$this->getUsers()]);

    }
	
	public function get(Request $request)
    {

		//$orderby = 'updated_at';
		$order_column = $request->input('order.0.column','14');

		if($order_column == 14){
			$orderby = 'updated_at';
		}else if($order_column == 6){
			$orderby = 'transfer_amount';
		}else if($order_column == 1){
			$orderby = 'created_at';
		}else{
			$orderby = 'updated_at';
		}

        $sort = $request->input('order.0.dir','desc');

        if ($request->input("customActionType") == "group_action") {
			   if(!Auth::user()->admin) die('Permission denied');
			   $updateDate = [];
			   $updateDate['step'] = $request->input("customstatus");
			   RsgRequest::whereIn('id',$request->input("id"))->update($updateDate);
        }
		$date_from=$request->input('date_from')?$request->input('date_from'):date('Y-m-d',strtotime('- 90 days'));
        $date_to=$request->input('date_to')?$request->input('date_to'):date('Y-m-d');

		$submit_date_from=$request->input('submit_date_from')?$request->input('submit_date_from'):date('Y-m-d',strtotime('- 90 days'));
		$submit_date_to=$request->input('submit_date_to')?$request->input('submit_date_to'):date('Y-m-d');

		$datas= RsgRequest::leftJoin('rsg_products',function($q){
				$q->on('rsg_requests.product_id', '=', 'rsg_products.id');
			})->where('rsg_requests.updated_at','>=',$date_from.' 00:00:00')->where('rsg_requests.updated_at','<=',$date_to.' 23:59:59')->where('rsg_requests.created_at','>=',$submit_date_from.' 00:00:00')->where('rsg_requests.created_at','<=',$submit_date_to.' 23:59:59');
               
        if($request->input('customer_email')){
            $datas = $datas->where('customer_email', $request->input('customer_email'));
        }
		if($request->input('step')){
            $datas = $datas->where('step', $request->input('step'));
        }
		if($request->input('asin')){
            $datas = $datas->where('asin', $request->input('asin'));
        }
		if($request->input('price_from')){
            $datas = $datas->where('transfer_amount','>=', round($request->input('price_from'),2));
        }
		if($request->input('price_to')){
            $datas = $datas->where('transfer_amount','<=', round($request->input('price_to'),2));
        }
		
		if($request->input('customer_paypal_email')){
            $datas = $datas->where('customer_paypal_email', $request->input('customer_paypal_email'));
        }
		
		if($request->input('amazon_order_id')){
            $datas = $datas->where('amazon_order_id', $request->input('amazon_order_id'));
        }

		if($request->input('review_url')){
            $datas = $datas->where('review_url','like', '%'.$request->input('review_url').'%');
        }

		if($request->input('star_rating')){
			$datas = $datas->where('star_rating', $request->input('star_rating'));
		}

		if($request->input('follow')){
			$datas = $datas->where('follow','like', '%'.$request->input('follow').'%');
		}

		if($request->input('next_follow_date')){
			$datas = $datas->where('next_follow_date','like', '%'.$request->input('next_follow_date').'%');
		}

		if($request->input('user_id')){
			$datas = $datas->where('rsg_products.user_id', $request->input('user_id'));
		}

		if($request->input('site')){
			$datas = $datas->where('rsg_products.site','like', '%'.$request->input('site').'%');
		}
		
		$iTotalRecords = $datas->count();
        $iDisplayLength = intval($_REQUEST['length']);
        $iDisplayLength = $iDisplayLength < 0 ? $iTotalRecords : $iDisplayLength;
        $iDisplayStart = intval($_REQUEST['start']);
        $sEcho = intval($_REQUEST['draw']);
		$lists =  $datas->orderBy($orderby,$sort)->offset($iDisplayStart)->limit($iDisplayLength)->get(['rsg_requests.*','rsg_products.asin','rsg_products.site','rsg_products.seller_id','rsg_products.user_id'])->toArray();
        $records = array();
        $records["data"] = array();

        $end = $iDisplayStart + $iDisplayLength;
        $end = $end > $iTotalRecords ? $iTotalRecords : $end;
		$accounts = $this->getAccounts();
		$users= $this->getUsers();
		$status_arr = array(0=>'<span class="badge badge-default">Disabled</a>',1=>'<span class="badge badge-success">Enabled</span>');
		foreach ( $lists as $list){
            $records["data"][] = array(
                '<label class="mt-checkbox mt-checkbox-single mt-checkbox-outline"><input name="id[]" type="checkbox" class="checkboxes" value="'.$list['id'].'"/><span></span></label>',
				$list['created_at'],
				$list['customer_email'],
				'<a href="https://'.array_get($list,'site').'/dp/'.array_get($list,'asin').'?m='.array_get($list,'seller_id').'" target="_blank">'.$list['asin'].'</a>',
				'<span class="badge badge-success">'.array_get(getStepStatus(),$list['step']).'</span>',
				$list['customer_paypal_email'],
                $list['transfer_amount'].' '.$list['transfer_currency'],
				$list['amazon_order_id'],
				'<div style="width: 200px;word-wrap: break-word;text-align: center;">'.$list['review_url'].'<BR><span class="text-danger">'.$list['transaction_id'].'</span></div>',
				$list['star_rating'],
				$list['follow'],
				$list['next_follow_date'],
				array_get($users,$list['user_id']),
				$list['site'],
				$list['updated_at'],
				'<a data-target="#ajax" data-toggle="modal" href="'.url('rsgrequests/'.$list['id'].'/edit').'" class="badge badge-success"> View </a> <a class="btn btn-danger btn-xs" href="'.url('rsgrequests/process?email='.$list['customer_email']).'" target="_blank">Process</a>'
				
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

	public function process(Request $req){

		if ($req->isMethod('GET')) {

			$emails = DB::table('sendbox')->where('to_address', $req->input('email'))->orderBy('date', 'desc')->get();
			$emails = json_decode(json_encode($emails), true); // todo

			$users= $this->getUsers();
		}
		return view('rsgrequests/process',['emails'=>$emails,'users'=>$users]);
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
        return view('rsgrequests/add',['products'=>self::getproducts()]);
    }


    public function store(Request $request)
    {
        //if(!Auth::user()->admin) die('Permission denied');
        $this->validate($request, [
			'step' => 'required|int',
			'product_id' => 'required|int',
			'customer_email' => 'required|email'
        ]);
		
        $rule = new RsgRequest();
		$rule->customer_email = $request->get('customer_email');
		$rule->customer_paypal_email = $request->get('customer_paypal_email');
		$rule->transfer_paypal_account = $request->get('transfer_paypal_account');
		$rule->transaction_id = $request->get('transaction_id');
		$rule->amazon_order_id = $request->get('amazon_order_id');
		$rule->transfer_amount = round($request->get('transfer_amount'),2);
		$rule->transfer_currency = $request->get('transfer_currency');
		$rule->review_url = $request->get('review_url');
        $rule->step = intval($request->get('step'));
		if(intval($request->get('product_id'))) $rule->product_id = intval($request->get('product_id'));
		$rule->star_rating = $request->get('star_rating');
		$rule->follow = $request->get('follow');
		$rule->next_follow_date = $request->get('next_follow_date');

        $rule->user_id = intval(Auth::user()->id);
        if ($rule->save()) {
			$step_to_tags = array(
				'1'  => 'RSG Join',
				'2'  => 'RSG Request Reject',
				'3'  => 'RSG Submit Paypal',
				'4'  => 'RSG Check Paypal',
				'5'  => 'RSG Submit Purchase',
				'6'  => 'RSG Check Purchase',
				'7'  => 'RSG Submit Review Url',
				'8'  => 'RSG Check Review Url',
				'9'  => 'RSG Completed'
			);
			$product= RsgProduct::where('id',$rule->product_id)->first()->toArray();
			$mailchimpData = array(
				'PROIMG'=>$product['product_img'],'PRONAME'=>$product['product_name'],'PROKEY'=>$product['keyword'],'PROPAGE'=>$product['page'],'PROPOS'=>$product['position']
			);
			if($rule->customer_paypal_email) $mailchimpData['PAYPAL'] = $rule->customer_paypal_email;
			if($rule->transfer_amount) $mailchimpData['FUNDED'] = $rule->transfer_amount.' '.$rule->transfer_currency;
			if($rule->amazon_order_id) $mailchimpData['ORDERID'] = $rule->amazon_order_id;
			if($rule->review_url) $mailchimpData['REVIEWURL'] = $rule->review_url;
			self::mailchimp($rule->customer_email,array_get($step_to_tags,$rule->step),[
						'email_address' => $rule->customer_email,
						'status'        => 'subscribed',
						'merge_fields' => $mailchimpData]);
            $request->session()->flash('success_message','Set Rsg Request Success');
            return redirect('rsgrequests');
        }else{
            $request->session()->flash('error_message','Set Rsg Request Failed');
            return redirect()->back()->withInput();
        }
    }
	
    public function edit(Request $request,$id)
    {
        $rule= RsgRequest::where('id',$id)->first()->toArray();
        if(!$rule){
            $request->session()->flash('error_message','Rsg Product not Exists');
            return redirect('rsgrequests');
        }
		if(array_get($rule,'customer_paypal_email')) $rule['trans']=self::getTrans(array_get($rule,'customer_paypal_email'));
		$product= RsgProduct::where('id',$rule['product_id'])->first()->toArray();
        return view('rsgrequests/edit',['rule'=>$rule,'product'=>$product,'products'=>self::getproducts()]);
    }
	
	public function getproducts(){
		$date=date('Y-m-d');
		$products = RsgProduct::where('status',1)->where('daily_remain','>',0)->where('start_date','<=',$date)->where('end_date','>=',$date)->get()->toArray();
		return $products;
	}

    public function update(Request $request,$id)
    {
		//if(!Auth::user()->admin) die('Permission denied');
        $this->validate($request, [
			'step' => 'required|int',
        ]);
		
        $rule = RsgRequest::findOrFail($id);
		$rule->customer_paypal_email = $request->get('customer_paypal_email');
		$rule->transfer_paypal_account = $request->get('transfer_paypal_account');
		$rule->transaction_id = $request->get('transaction_id');
		$rule->amazon_order_id = $request->get('amazon_order_id');
		$rule->transfer_amount = round($request->get('transfer_amount'),2);
		$rule->transfer_currency = $request->get('transfer_currency');
		$rule->review_url = $request->get('review_url');
        $rule->step = intval($request->get('step'));
		if(intval($request->get('product_id'))) $rule->product_id = intval($request->get('product_id'));
		$rule->star_rating = $request->get('star_rating');
		$rule->follow = $request->get('follow');
		$rule->next_follow_date = $request->get('next_follow_date');

        $rule->user_id = intval(Auth::user()->id);
        if ($rule->save()) {
			$step_to_tags = array(
				'1'  => 'RSG Join',
				'2'  => 'RSG Request Reject',
				'3'  => 'RSG Submit Paypal',
				'4'  => 'RSG Check Paypal',
				'5'  => 'RSG Submit Purchase',
				'6'  => 'RSG Check Purchase',
				'7'  => 'RSG Submit Review Url',
				'8'  => 'RSG Check Review Url',
				'9'  => 'RSG Completed'
			);
			$product= RsgProduct::where('id',$rule->product_id)->first()->toArray();
			$mailchimpData = array(
				'PROIMG'=>$product['product_img'],'PRONAME'=>$product['product_name'],'PROKEY'=>$product['keyword'],'PROPAGE'=>$product['page'],'PROPOS'=>$product['position']
			);
			if($rule->customer_paypal_email) $mailchimpData['PAYPAL'] = $rule->customer_paypal_email;
			if($rule->transfer_amount) $mailchimpData['FUNDED'] = $rule->transfer_amount.' '.$rule->transfer_currency;
			if($rule->amazon_order_id) $mailchimpData['ORDERID'] = $rule->amazon_order_id;
			if($rule->review_url) $mailchimpData['REVIEWURL'] = $rule->review_url;
			self::mailchimp($rule->customer_email,array_get($step_to_tags,$rule->step),[
						'email_address' => $rule->customer_email,
						'status'        => 'subscribed',
						'merge_fields' => $mailchimpData]);
            $request->session()->flash('success_message','Set Rsg Request Success');
            return redirect('rsgrequests');
        }else{
            $request->session()->flash('error_message','Set Rsg Request Failed');
            return redirect()->back()->withInput();
        }
    }
	
	
	public function mailchimp($customer_email,$tag,$args){
		$MailChimp = new MailChimp(env('MAILCHIMP_KEY', ''));
		//$MailChimp->verify_ssl=false;
		$list_id = '6aaf7d9691';
		$subscriber_hash = $MailChimp->subscriberHash($customer_email);	
		$MailChimp->put("lists/$list_id/members/$subscriber_hash", $args);
		if (!$MailChimp->success()) {
			die($MailChimp->getLastError());
		}
		$MailChimp->post("lists/$list_id/members/$subscriber_hash/tags", [
			'tags'=>[
			['name' => $tag,
			'status' => 'active',]
			]
		]);
		if (!$MailChimp->success()) {
			die($MailChimp->getLastError());
		}
	}
	
	public function getTrans($customer_paypal_email){
		$transactionSearchRequest = new TransactionSearchRequestType();
		$transactionSearchRequest->StartDate='2018-01-01T00:00:00Z';
		$transactionSearchRequest->EndDate=date('Y-m-d\TH:i:s\Z');
		$transactionSearchRequest->Payer=$customer_paypal_email;
		$tranSearchReq = new TransactionSearchReq();
		$tranSearchReq->TransactionSearchRequest = $transactionSearchRequest;
		$config = array(
			"acct1.UserName" => env('PAYPAL_USERNAME', ''),
			"acct1.Password" => env('PAYPAL_PASSWORD', ''),
			"acct1.Signature" => env('PAYPAL_SIGNATURE', ''),
			"mode" => "live",
			'log.LogEnabled' => false,
			'log.FileName' => '../PayPal.log',
			'log.LogLevel' => 'FINE'
		);
		$paypalService = new PayPalAPIInterfaceServiceService($config);
		$transactionSearchResponse = $paypalService->TransactionSearch($tranSearchReq);
		$transactionSearchResponse = json_decode(json_encode($transactionSearchResponse), true);
		return array_get($transactionSearchResponse,'PaymentTransactions',[]);
	}

	public function export(){

		set_time_limit(0);

		$arrayData = array();
		$headArray[] = 'Submit Date';
		$headArray[] = 'Customer Email';
		$headArray[] = 'Request Product';
		$headArray[] = 'Current Step';
		$headArray[] = 'Customer Paypal';
		$headArray[] = 'Funded';
		$headArray[] = 'Amazon OrderID';
		$headArray[] = 'Review Url';
		$headArray[] = 'Star rating';
		$headArray[] = 'Follow';
		$headArray[] = 'Next follow date';
		$headArray[] = 'User';
		$headArray[] = 'Site';
		$headArray[] = 'Update Date';

		$arrayData[] = $headArray;

		$orderby = 'updated_at';
		$sort = 'desc';
		$datas= RsgRequest::leftJoin('rsg_products',function($q){
			$q->on('rsg_requests.product_id', '=', 'rsg_products.id');
		});

		//$datas->count();
		$lists =  $datas->orderBy($orderby,$sort)->get(['rsg_requests.*','rsg_products.asin','rsg_products.site','rsg_products.seller_id','rsg_products.user_id'])->toArray();
		$users = $this->getUsers();

		foreach ($lists as $key=>$val){

			$arrayData[] = array(
				$val['created_at'],
				$val['customer_email'],
				$val['asin'],
				array_get(getStepStatus(),$val['step']),
				$val['customer_paypal_email'],
				$val['transfer_amount'].$val['transfer_currency'],
				$val['amazon_order_id'],
				$val['review_url'].$val['transaction_id'],
				$val['star_rating'],
				$val['follow'],
				$val['next_follow_date'],
				array_get($users,$val['user_id']),
				$val['site'],
				$val['updated_at'],
			);
		}

		if($arrayData){
			$spreadsheet = new Spreadsheet();

			$spreadsheet->getActiveSheet()
				->fromArray(
					$arrayData,  // The data to set
					NULL,        // Array values with this value will not be set
					'A1'         // Top left coordinate of the worksheet range where
				//    we want to set these values (default is A1)
				);
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//告诉浏览器输出07Excel文件
			header('Content-Disposition: attachment;filename="Export_RSG_Requests.xlsx"');//告诉浏览器输出浏览器名称
			header('Cache-Control: max-age=0');//禁止缓存
			$writer = new Xlsx($spreadsheet);
			$writer->save('php://output');
		}
		die();
	}


}