<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\User;
use App\Models\TransferTask;
use App\Models\TransferPlan;
use App\Models\TransferRequest;
use Illuminate\Support\Facades\Auth;
use PDO;
use DB;
use Illuminate\Http\Response;
class TransferPlanController extends Controller
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
        parent::__construct();
    }

    public function index()
    {
        if(!Auth::user()->can(['transfer-plan-show'])) die('Permission denied -- transfer-plan-show');
        return view('transfer/planList',['sellers'=>getUsers('sap_seller'), 'users'=>getUsers(), 'status'=>TransferPlan::STATUS, 'siteCode' => (DB::table('marketplaces')->pluck('country_code','marketplace_id'))]);

    }

    public function get(Request $request)
    {
        $datas = TransferPlan::select('transfer_plans.*','transfer_requests.marketplace_id','transfer_requests.bg','transfer_requests.bu','transfer_requests.asin','transfer_requests.sku as request_sku'
        ,'transfer_requests.quantity as request_quantity','transfer_tasks.transfer_task_key','transfer_tasks.status as task_status','transfer_tasks.carrier_code as task_carrier_code'
        ,'transfer_tasks.ship_method as task_ship_method','transfer_tasks.tracking_number','transfer_tasks.out_date as task_out_date','transfer_tasks.in_date as task_in_date','asin.fba_stock',
        'asin.fba_transfer','asin.sales')
        ->leftJoin('transfer_requests',function($q){
            $q->on('transfer_plans.transfer_request_id', '=', 'transfer_requests.id');
        })
        ->leftjoin('transfer_tasks',function($q){
            $q->on('transfer_plans.id', '=', 'transfer_tasks.transfer_plan_id');
        })
        ->leftjoin('marketplaces',function($q){
            $q->on('transfer_requests.marketplace_id', '=', 'marketplaces.marketplace_id');
        })
        ->leftJoin(DB::raw("(select sum(fba_stock) as fba_stock,sum(fba_transfer) as fba_transfer,sum(sales_07_01*0.1+sales_14_08*0.2+sales_21_15*0.3+sales_28_22*0.5)/7 as sales ,asin,site from asin where length(asin)=10 group by asin,site) as asin"),function($q){
            $q->on('transfer_requests.asin', '=', 'asin.asin')->on('marketplaces.site', '=', 'asin.site');
        });
        


        if(array_get($_REQUEST,'marketplace_id')){
            $datas = $datas->whereIn('transfer_requests.marketplace_id',array_get($_REQUEST,'marketplace_id'));
        }
        if(array_get($_REQUEST,'bg')){
            $datas = $datas->whereIn('transfer_requests.bg',array_get($_REQUEST,'bg'));
        }
        if(array_get($_REQUEST,'bu')){
            $datas = $datas->whereIn('transfer_requests.bu',array_get($_REQUEST,'bu'));
        }
        if(array_get($_REQUEST,'out_factory')){
            $datas = $datas->where('out_factory',array_get($_REQUEST,'out_factory'));
        }
        if(array_get($_REQUEST,'in_factory')){
            $datas = $datas->where('in_factory',array_get($_REQUEST,'in_factory'));
        }
        if(array_get($_REQUEST,'asin')){
            $datas = $datas->whereIn('transfer_requests.asin',explode(',',str_replace([' ','	'],'',array_get($_REQUEST,'asin'))));
        }
        if(array_get($_REQUEST,'sku')){
            $datas = $datas->whereIn('transfer_requests.sku',explode(',',str_replace([' ','	'],'',array_get($_REQUEST,'sku'))));
        } 
        if(array_get($_REQUEST,'status')!==NULL && array_get($_REQUEST,'status')!==''){
            $datas = $datas->whereIn('transfer_plans.status',array_get($_REQUEST,'status'));
        }
        
        $iTotalRecords = $datas->count();
        $iDisplayLength = intval($_REQUEST['length']);
        $iDisplayLength = $iDisplayLength < 0 ? $iTotalRecords : $iDisplayLength;
        $iDisplayStart = intval($_REQUEST['start']);
        $sEcho = intval($_REQUEST['draw']);
        $lists =  $datas->offset($iDisplayStart)->limit($iDisplayLength)->orderBy('transfer_plans.id','desc')->get()->toArray();
        
        $records["data"] = array();
        $siteCode = DB::table('marketplaces')->pluck('country_code','marketplace_id');
		foreach ( $lists as $list){
            $records["data"][] = array(
                '<input name="id[]" type="checkbox" class="checkboxes" value="'.$list['id'].'"  />',
                array_get($siteCode,$list['marketplace_id']),
                $list['bg'],
                $list['bu'],
                $list['out_factory'],
                $list['in_factory'],
                $list['asin'],
                $list['request_sku'],
                $list['request_quantity'],
                ($list['status']!==NUll)?array_get(TransferPlan::STATUS,$list['status']):'',
                $list['sku'],
                $list['quantity'],
                $list['fba_stock']??0,
                $list['fba_transfer']??0,
                ($list['sales']>0)?date('Y-m-d',strtotime('+'.intval((array_get($list,'fba_stock',0)+array_get($list,'fba_transfer',0))/$list['sales']).' days')):'∞',
                $list['carrier_code'].($list['ship_method']?'</BR>'.$list['ship_method']:''),
                $list['out_date'],
                $list['in_date'],
                $list['require_rms']?'Yes':'No',
                $list['require_attach']?'Yes':'No',
                $list['require_purchase']?'Yes':'No',
                $list['require_rebrand']?'Yes':'No',
                $list['transfer_task_key'],
                ($list['task_status']!==NUll)?array_get(TransferTask::STATUS,$list['task_status']):'',
                ($list['tracking_number']?$list['tracking_number']:'').($list['task_carrier_code']?'</BR>'.$list['task_carrier_code']:'').($list['task_ship_method']?'</BR>'.$list['task_ship_method']:''),
                $list['task_out_date'],
				$list['task_in_date'],
            );
		}
        $records["draw"] = $sEcho;
        $records["recordsTotal"] = $iTotalRecords;
        $records["recordsFiltered"] = $iTotalRecords;
        echo json_encode($records);
    }

    public function edit(Request $request,$id)
    {
        if(!Auth::user()->can(['transfer-plan-show'])) die('Permission denied -- transfer-plan-show');
        $transferPlan =  TransferPlan::find($id);
        if(empty($transferPlan)) die('计划不存在!');
        $transferRequest = TransferRequest::find($transferPlan->transfer_request_id);
        $transferTask = TransferTask::where('transfer_plan_id',$id)->first();

        $users = getUsers();
        $siteCode = DB::table('marketplaces')->pluck('country_code','marketplace_id');
        $accountCode = DB::connection('amazon')->table('seller_accounts')->whereNull('deleted_at')->pluck('label','mws_seller_id');
        $logArr = [];
        if(!empty($transferTask)){
        $logs = getOperationLog(['table'=>'transfer_tasks','primary_id'=>$transferTask->id]);
            foreach($logs  as $log){
                $logArr[]= $log->created_at.' '.array_get($users,$log->user_id).' '.array_get(TransferTask::STATUS,array_get(json_decode($log->input,true),'status')); 
            }
        }
        
        return view('transfer/planEdit',['transferPlan'=>$transferPlan,'transferRequest'=>$transferRequest,'transferTask'=>$transferTask,'sellers'=>getUsers('sap_seller'), 'users'=>$users, 'planStatus'=>TransferPlan::STATUS, 'requestStatus'=>TransferRequest::STATUS, 'taskStatus'=>TransferTask::STATUS,'logArr'=>$logArr,'siteCode'=>$siteCode,'accountCode'=>$accountCode]);
    }
	

    public function update(Request $request,$id)
    {
		if(!Auth::user()->can(['transfer-plan-update'])) die('Permission denied -- transfer-plan-update');
        DB::beginTransaction();
        try{ 
            $data = TransferPlan::findOrFail($id);
            if($data->status == 1 ) throw new \Exception("计划已审核，无法再次更新!");
            $fileds = array(
                'out_factory','out_date','in_factory','in_date','sku','quantity','rms','carrier_code','ship_method','require_rms','require_attach','require_purchase','require_rebrand','status'
            );
            foreach($fileds as $filed){
                $data->{$filed} = $request->get($filed);
            }
            $data->save();
            if($data->status == 1 ) self::createTransferTask($data);
            saveOperationLog('transfer_plans', $data->id, $request->all());
            DB::commit();
            $records["customActionStatus"] = 'OK';
            $records["customActionMessage"] = "更新成功!";     
        }catch (\Exception $e) { 
            DB::rollBack();
            $records["customActionStatus"] = '';
            $records["customActionMessage"] = $e->getMessage();
        }
        echo json_encode($records);
    }

    public function batchUpdate(Request $request){
        if(!Auth::user()->can(['transfer-plan-update'])) die('Permission denied -- transfer-plan-update');
        $status = intval(array_get($_REQUEST,"confirmStatus"));
        DB::beginTransaction();
        try{ 
            $customActionMessage='';
            if($status == 1) $transferTaskKey = uniqid('Task');
            foreach($_REQUEST["id"] as $plan_id){
                $transferPlan = TransferPlan::where('status','<>',1)->find($plan_id);
                if(empty($transferPlan)){
                    $customActionMessage.='ID:'.$plan_id.' 已审核或不存在!</BR>';
                    continue;
                }
                if($transferPlan->status == $status){
                    $customActionMessage.='ID:'.$plan_id.' 无更新!</BR>';
                    continue;
                }
                $transferPlan->status = $status;
                $transferPlan->save();
                $customActionMessage.='ID:'.$plan_id.' 更新成功!</BR>';
                if($status == 1) self::createTransferTask($transferPlan,$transferTaskKey);
                saveOperationLog('transfer_plans', $transferPlan->id, ['status'=>$status]);
            }
            DB::commit();
            $records["customActionStatus"] = 'OK';
            $records["customActionMessage"] = $customActionMessage;     
        }catch (\Exception $e) { 
            DB::rollBack();
            $records["customActionStatus"] = '';
            $records["customActionMessage"] = $e->getMessage();
        }    
        echo json_encode($records);   

    }
    public function createTransferTask(TransferPlan $transferPlan,string $transferTaskKey = ''){
        if(!$transferTaskKey) $transferTaskKey = uniqid('Task');
        $status = 3;
        if($transferPlan->require_rebrand) $status=2;
        if($transferPlan->require_purchase) $status=1;
        if($transferPlan->require_attach) $status=0;
        $result = TransferTask::firstOrCreate(
            [
                'transfer_plan_id'=>$transferPlan->id
            ],
            [
                'transfer_task_key'=>$transferTaskKey,
                'status'=>$status,
                'user_id'=>Auth::user()->id
            ]
        );
        if($result->wasRecentlyCreated) saveOperationLog('transfer_tasks', $result->id, ['status'=>$status]);
    }

	//生成计划
	public function createPlan(Request $request)
	{
		$userInfo = Auth::user();//登录用户信息
		if(!$userInfo->can(['transfer-plan-add'])) die('Permission denied -- transfer-plan-add');
		if($request->isMethod('get')){
			//调出生成计划页面
			$ids = isset($_REQUEST['id']) && $_REQUEST['id'] ? $_REQUEST['id'] : '';
			$id_array = explode(',',$ids);
			$data = DB::table('transfer_requests')->where('status',6)->whereIn('id',$id_array)->select('id','asin','sku','quantity','seller_id','marketplace_id')->get()->toArray();//获取此id的数据
			if(empty($data)){
				$request->session()->flash('error_message','ID error');
				return redirect()->back()->withInput();
			}else{
				$site_sellerid = array();
				foreach($data as $key=>$val){
					$data[$key] = (array)$val;
					$site_sellerid[$val->marketplace_id.'_'.$val->seller_id] = isset($site_sellerid[$val->marketplace_id.'_'.$val->seller_id]) ? $site_sellerid[$val->marketplace_id.'_'.$val->seller_id] : '';
				}
				if(count($site_sellerid)>1){//判断是否可同时添加
					$request->session()->flash('error_message','同时生成多条计划的时候，只能为同一站点同一账号');
					return redirect()->back()->withInput();
				}
			}
			return view('transfer/planAdd',['data'=>$data,'ids'=>$ids]);
		}elseif ($request->isMethod('post')){
			//post请求生成计划操作
			$insert['status'] = 0;
			$insert['planer'] = $userInfo->id;
			$insert['transfer_plan_key'] = md5(time());

			$configField = array('out_factory','in_factory','carrier_code','ship_method','out_date','in_date');
			foreach($configField as $field){
				$insert[$field] = isset($_POST[$field]) && $_POST[$field]!=='' ? $_POST[$field] : '';
			}
			$id_array = isset($_POST['id']) ? $_POST['id'] : '';
			$quantity = isset($_POST['quantity']) ? $_POST['quantity'] : '';
			$require_rms = isset($_POST['require_rms']) ? $_POST['require_rms'] : '';
			$require_attach = isset($_POST['require_attach']) ? $_POST['require_attach'] : '';
			$require_rebrand = isset($_POST['require_rebrand']) ? $_POST['require_rebrand'] : '';
			$num = 0;
			foreach($id_array as $key=>$val){
				$insert['transfer_request_id'] = $val;
				$insert['created_at'] = $insert['updated_at'] = date('Y-m-d H:i:s');
				$insert['quantity'] = isset($quantity[$key]) ? $quantity[$key] : 0;
				$insert['require_rms'] = isset($require_rms[$key]) ? $require_rms[$key] : 0;
				$insert['require_attach'] = isset($require_attach[$key]) ? $require_attach[$key] : 0;
				$insert['require_rebrand'] = isset($require_rebrand[$key]) ? $require_rebrand[$key] : 0;
				//添加表的数据
				$resId = DB::table('transfer_plans')->insertGetId($insert);
				if($resId){
					DB::table('transfer_requests')->where('id',$val)->update(array('status'=>8));
					SaveOperationLog('transfer_requests', $resId, $insert);//添加操作存日志
					$num++;
				}
			}
			if($num==0){
				$request->session()->flash('error_message','Add Failed');
				return redirect()->back()->withInput();
			}

			return redirect('/transferPlan');
		}
	}
}