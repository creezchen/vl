<?php

namespace App\Http\Controllers\Hijack;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use log;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Asin;

header('Access-Control-Allow-Origin:*');

class HijackController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */


    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function index()
    {
        return view('hijack.index');
    }

    public function detail()
    {
        return view('hijack.detail');
    }

    /**
     * 首页接口请求
     * 备用
     * @return mixed
     */
    public function index2()
    {
        header('Access-Control-Allow-Origin:*');
        //得到登录用户信息
        // $user = Auth::user()->toArray();
        //查询所有 asin 信息
        $DOMIN_MARKETPLACEID_SX = Asin::DOMIN_MARKETPLACEID_SX;
        $DOMIN_MARKETPLACEID = Asin::DOMIN_MARKETPLACEID;
        $DOMIN_MARKETPLACEID_RUL = Asin::DOMIN_MARKETPLACEID_URL;
        $productList = DB::connection('vlz')->table('asins')
            ->select('id', 'asin', 'images', 'marketplaceid', 'title', 'images', 'listed_at', 'mpn', 'seller_count', 'updated_at', 'reselling_switch')
            ->whereNotNull('title')
            ->groupBy(['asin'])
            ->orderBy('updated_at', 'desc')
            ->get(['asin'])->map(function ($value) {
                return (array)$value;
            })->toArray();
        $asinList = [];
        if (!empty($productList)) {
            foreach ($productList as $key => $value) {
                $asinList[$value['id']] = $value['asin'];
                $productList[$key]['domin_sx'] = $DOMIN_MARKETPLACEID_SX[$value['marketplaceid']];
                $asinIdList[] = $value['id'];
            }
        }

        //查询跟卖数据
        $resellingidList = [];
        if (!empty($asinIdList)) {
            $resellingList = DB::connection('vlz')->table('tbl_reselling_asin')
                ->select('id', 'asin', 'product_id')
                ->whereIn('product_id', array_unique($asinIdList))
                ->get()->map(function ($value) {
                    return (array)$value;
                })->toArray();
        }

        if (!empty($resellingList)) {
            foreach ($resellingList as $rlk => $rlv) {
                $resellingidList[] = $rlv['id'];
            }
            //查询对应的asin 下面 跟卖数量
            $taskList = DB::connection('vlz')->table('tbl_reselling_task')
                ->select('id', 'reselling_num', 'reselling_time', 'created_at', 'reselling_asin_id')
                ->whereIn('reselling_asin_id', array_unique($resellingidList))
                ->groupBy('reselling_asin_id')
                ->orderBy('reselling_time', 'desc')
                ->get()->map(function ($value) {
                    return (array)$value;
                })->toArray();
            foreach ($resellingList as $rltk => $rltv) {
                $resellingList[$rltk]['reselling_num'] = '';
                $resellingList[$rltk]['reselling_time'] = '';
                foreach ($taskList as $tlk => $tlv) {
                    if ($rltv['id'] == $tlv['reselling_asin_id']) {
                        $resellingList[$rltk]['reselling_num'] = $tlv['reselling_num'];
                        $resellingList[$rltk]['reselling_time'] = $tlv['reselling_time'];
                    }
                }
            }
        }
        //中间对应关系数据
        $sap_asin_match_sku = DB::connection('vlz')->table('sap_asin_match_sku')
            ->select('sap_seller_id', 'asin', 'sap_seller_bg', 'sap_seller_bu', 'id', 'status', 'updated_at', 'sku_status', 'sku')
            ->whereIn('asin', array_unique($asinList))
            ->groupBy('asin')
            ->get()->map(function ($value) {
                return (array)$value;
            })->toArray();
        $sap_seller_id_list = [];
        if (!empty($sap_asin_match_sku)) {
            foreach ($sap_asin_match_sku as $k => $v) {
                if (!in_array($v['sap_seller_id'], $sap_seller_id_list)) {
                    $sap_seller_id_list[$v['sap_seller_id']]['asin'] = $v['asin'];
                    $sap_seller_id_list[$v['sap_seller_id']]['BG'] = $v['sap_seller_bg'];
                    $sap_seller_id_list[$v['sap_seller_id']]['BU'] = $v['sap_seller_bu'];
                    $sap_seller_id_list[$v['sap_seller_id']]['sku'] = $v['sku'];
                    $sap_seller_id_list[$v['sap_seller_id']]['sap_updated_at'] = $v['updated_at'];
                    $sap_seller_id_list[$v['sap_seller_id']]['sku_status'] = $v['sku_status'];

                }
            }
        }
        $userList = DB::table('users')->select('id', 'name', 'email', 'sap_seller_id')
            ->whereIn('sap_seller_id', array_keys($sap_seller_id_list))
            ->get()->map(function ($value) {
                return (array)$value;
            })->toArray();
        if (!empty($userList)) {
            foreach ($userList as $uk => $uv) {
                foreach ($sap_seller_id_list as $sk => $sv) {
                    if ($uv['sap_seller_id'] == $sk) {
                        $userList[$uk]['asin'] = $sv['asin'];
                        $userList[$uk]['BG'] = $sv['BG'];
                        $userList[$uk]['BU'] = $sv['BU'];
                        $userList[$uk]['sku'] = $sv['sku'];
                        $userList[$uk]['sku_status'] = $sv['sku_status'];
                        $userList[$uk]['sap_updated_at'] = $sv['sap_updated_at'];
                    }
                }

            }
        }
        foreach ($productList as $pk => $pv) {
            $productList[$pk]['userName'] = '';
            $productList[$pk]['email'] = '';
            $productList[$pk]['BG'] = '';
            $productList[$pk]['BU'] = '';
            $productList[$pk]['sku'] = '';
            $productList[$pk]['sku_status'] = '';
            $productList[$pk]['sap_updated_at'] = '';
            foreach ($userList as $ulk => $ulv) {
                if ($pv['asin'] == $ulv['asin']) {
                    $productList[$pk]['userName'] = $ulv['name'];
                    $productList[$pk]['email'] = $ulv['email'];
                    $productList[$pk]['BG'] = $ulv['BG'];
                    $productList[$pk]['BU'] = $ulv['BU'];
                    $productList[$pk]['sku'] = $ulv['sku'];
                    $productList[$pk]['sku_status'] = $ulv['sku_status'];
                    $productList[$pk]['sap_updated_at'] = $ulv['sap_updated_at'];
                }
            }
            $productList[$pk]['reselling_num'] = '';
            $productList[$pk]['reselling_time'] = '';
            foreach ($resellingList as $resk => $resv) {
                if ($pv['id'] == $resv['product_id']) {
                    $productList[$pk]['reselling_num'] = $resv['reselling_num'];
                    $productList[$pk]['reselling_time'] = $resv['reselling_time'] ? date('Y/m/d H:i:s', $resv['reselling_time']) : '';
                }
            }
            $productList[$pk]['toUrl'] = $DOMIN_MARKETPLACEID_RUL[$pv['marketplaceid']];
        }
        $returnDate['userList'] = $userList;
        $returnDate['productList'] = $productList;
        return $returnDate;
    }

    /**
     * 首页接口请求
     * 目前使用
     * @return mixed
     */
    public function index1()
    {
        $admin = array("charlie@valuelinkcorp.com", "zouyuanxun@valuelinkcorp.com", "zanhaifang@valuelinkcorp.com", "huzaoli@valuelinkcorp.com", 'fanlinxi@valuelinkcorp.com');
        $userasinL = [];
        $sapSellerIdList = [];
//        $user = ['id' => '367',
//            'name' => 'test',
//            'email' => 'zanhaifang@valuelinkcorp.com',
//            'created_at' => '2020-03-27 14:58:11',
//            'updated_at' => '2020-03-27 14:58:11',
//            'admin' => 0,
//            'sap_seller_id' => 279,
//            'seller_rules' => 'BG3-BU3-* ',
//            'locked' => 0,
//            'ubg' => 'BG3',
//            'ubu' => 'BU3',
//        ];
        $bool_admin=0;//是否是管理员
        //!empty(Auth::user()->toArray())
        if (!empty(Auth::user()->toArray()) ) {
            $user = Auth::user()->toArray(); //todo  打开
            if (!empty($user['email']) && in_array($user['email'], $admin)) {
                //特殊权限着
                $bool_admin=1;
            } else if ($user['ubu'] != '' || $user['ubg'] != '' || $user['seller_rules'] != '') {
                //判断是否是销售 及 对应领导角色
                $allUsers = DB::table('users')->select('id', 'sap_seller_id', 'ubg', 'ubu')
                    ->where('ubg', $user['ubg'])
                    ->get()->map(function ($value) {
                        return (array)$value;
                    })->toArray();
                if ($user['ubu'] == '' && $user['ubg'] != '' && $user['seller_rules'] != '') {
                    /**查询所有BG下面员工*/
                    if (!empty($allUsers)) {
                        foreach ($allUsers as $auk => $auv) {
                            $sapSellerIdList[] = $auv['sap_seller_id'];
                        }
                    }
                } else if ($user['ubu'] != '' && $user['seller_rules'] == '') {
                    /**此条件为 普通销售*/
                    $sapSellerIdList[] = $user['sap_seller_id'];
                } else if ($user['ubu'] != '' && $user['ubg'] != '' && $user['seller_rules'] != '') {
                    /**  bu 负责人 及所有下属 */
                    if (!empty($allUsers)) {
                        foreach ($allUsers as $auk => $auv) {
                            if ($auv['ubu'] == $user['ubu']) {
                                $sapSellerIdList[] = $auv['sap_seller_id'];
                            }
                        }
                    }
                }
            } else {
                $err_message = ['status' => '-1', 'message' => 'No matching records found'];
                return $err_message;
            }

            if (!empty($sapSellerIdList)) {
                $user_asin_list = DB::connection('vlz')->table('sap_asin_match_sku')
                    ->select('asin')
                    ->whereIn('sap_seller_id', $sapSellerIdList)
                    ->groupBy('asin')
                    ->get()->map(function ($value) {
                        return (array)$value;
                    })->toArray();
                if (!empty($user_asin_list)) {
                    foreach ($user_asin_list as $uslk => $uslv) {
                        $userasinL[] = $uslv['asin'];
                    }
                }
            } else if($bool_admin==0){
                $err_message = ['status' => '-1', 'message' => 'No matching records found'];
                return $err_message;
            }
        }
        //查询所有 asin 信息
        $DOMIN_MARKETPLACEID_SX = Asin::DOMIN_MARKETPLACEID_SX;
        $DOMIN_MARKETPLACEID_RUL = Asin::DOMIN_MARKETPLACEID_URL;
        $sql_s = 'SELECT
            a.id,
            a.asin,
            a.images,
            a.marketplaceid,
            a.title,
            a.images,
            a.listed_at,
            a.mpn,
            a.seller_count,
            a.updated_at,
            a.reselling_switch,
            rl_asin.id AS rla_id,
            rl_task.id AS rlk_id,
            rl_task.reselling_num,
            rl_task.reselling_time,
            rl_task.created_at,
            rl_task.reselling_asin_id
            FROM(asins AS a LEFT JOIN tbl_reselling_asin AS rl_asin ON a.id = rl_asin.product_id)
            LEFT JOIN tbl_reselling_task AS rl_task ON rl_asin.id = rl_task.reselling_asin_id
            where a.title !="" ';
        // sa.sap_seller_id,
        //            sa.sap_seller_bg as BG,
        //            sa.sap_seller_bu as BU,
        //            sa.id AS sap_asin_id,
        //            sa. STATUS,
        //            sa.updated_at as sap_updated_at,
        //            sa.sku_status,
        //            sa.sku
        // LEFT JOIN sap_asin_match_sku AS sa ON sa.asin = a.asin
        $sql_seller = '';//' AND sa.sap_seller_id in (' . $sap_seller_id . ')';

        $sql_g = ' GROUP BY a.asin  ORDER BY a.reselling_switch DESC , rl_task.reselling_num DESC';
        /**  判断对应用户 以及对应管理人员 所有下属ID */
        if (!empty($userasinL)) {
            $sql_as = 'AND a.asin in ("' . implode($userasinL, '","') . '")';
            $sql = $sql_s . $sql_as . $sql_g;
        } else {
            $sql = $sql_s . $sql_g;
        }
        $productList_obj = DB::connection('vlz')->select($sql);
        $productList = (json_decode(json_encode($productList_obj), true));
        $asinList = [];
        if (!empty($productList)) {
            foreach ($productList as $key => $value) {
                $asinList[] = $value['asin'];
                $productList[$key]['domin_sx'] = $DOMIN_MARKETPLACEID_SX[$value['marketplaceid']];
                $productList[$key]['reselling_time'] = $value['reselling_time'] ? date('Y/m/d H:i:s', $value['reselling_time']) : '';
            }
        }

        //中间对应关系数据
        $sap_asin_match_sku = DB::connection('vlz')->table('sap_asin_match_sku')
            ->select('sap_seller_id', 'asin', 'sap_seller_bg', 'sap_seller_bu', 'id', 'status', 'updated_at', 'sku_status', 'sku')
            ->whereIn('asin', $asinList)
            //   ->whereIn('sap_seller_id',$sapSellerIdList)
            ->groupBy('asin')
            ->get()->map(function ($value) {
                return (array)$value;
            })->toArray();
        $sap_seller_id_list = [];
        if (!empty($sap_asin_match_sku)) {
            foreach ($sap_asin_match_sku as $k => $v) {
                if (!in_array($v['sap_seller_id'], $sap_seller_id_list)) {
                    $sap_seller_id_list[$v['sap_seller_id']]['asin'] = $v['asin'];
                    $sap_seller_id_list[$v['sap_seller_id']]['BG'] = $v['sap_seller_bg'];
                    $sap_seller_id_list[$v['sap_seller_id']]['BU'] = $v['sap_seller_bu'];
                    $sap_seller_id_list[$v['sap_seller_id']]['sku'] = $v['sku'];
                    $sap_seller_id_list[$v['sap_seller_id']]['sap_updated_at'] = $v['updated_at'];
                    $sap_seller_id_list[$v['sap_seller_id']]['sku_status'] = $v['sku_status'];
                }
            }
        }
        $userList = DB::table('users')->select('id', 'name', 'email', 'sap_seller_id')
            ->whereIn('sap_seller_id', array_keys($sap_seller_id_list))
            ->get()->map(function ($value) {
                return (array)$value;
            })->toArray();
        if (!empty($userList)) {
            foreach ($userList as $uk => $uv) {
                foreach ($sap_seller_id_list as $sk => $sv) {
                    if ($uv['sap_seller_id'] == $sk) {
                        $userList[$uk]['asin'] = $sv['asin'];
                        $userList[$uk]['BG'] = $sv['BG'];
                        $userList[$uk]['BU'] = $sv['BU'];
                        $userList[$uk]['sku'] = $sv['sku'];
                        $userList[$uk]['sku_status'] = $sv['sku_status'];
                        $userList[$uk]['sap_updated_at'] = $sv['sap_updated_at'];
                    }
                }
            }
            foreach ($productList as $pk => $pv) {
                $productList[$pk]['userName'] = '';
                $productList[$pk]['email'] = '';
                $productList[$pk]['BG'] = '';
                $productList[$pk]['BU'] = '';
                $productList[$pk]['sku'] = '';
                $productList[$pk]['sku_status'] = '';
                $productList[$pk]['sap_updated_at'] = '';
                foreach ($userList as $ulk => $ulv) {
                    if ($pv['asin'] == $ulv['asin']) {
                        $productList[$pk]['userName'] = $ulv['name'];
                        $productList[$pk]['email'] = $ulv['email'];
                        $productList[$pk]['BG'] = $ulv['BG'];
                        $productList[$pk]['BU'] = $ulv['BU'];
                        $productList[$pk]['sku'] = $ulv['sku'];
                        $productList[$pk]['sku_status'] = $ulv['sku_status'];
                        $productList[$pk]['sap_updated_at'] = $ulv['sap_updated_at'];
                    }
                }
            }
        }
        $returnDate['userList'] = $userList;
        $returnDate['productList'] = $productList;
        //   echo count($userList).'---'.count($productList);exit;
        return $returnDate;
    }

    /**
     * 接受post 请求 返回  ======= 暂未用到
     * @param Request $request
     * @return string
     */
    public function asinSearch(Request $request)
    {
        $param = $_REQUEST['param'] ? $_REQUEST['param'] : '';
        if (!empty($param)) {
            header('Access-Control-Allow-Origin:*');
            //得到登录用户信息
            // $user = Auth::user()->toArray();
            if ($request) {
                //   echo $request->params;
            }
            //查询用户列表
            $users = User::select('name', 'email')->where('locked', '=', '0')->get()->toArray();
//查询 like title or asin
            $productList = DB::connection('vlz')->table('asins')
                ->select('id', 'asin', 'images', 'marketplaceid', 'title', 'images', 'listed_at', 'mpn', 'seller_count', 'updated_at', 'reselling_switch')
                ->where('title', 'like', '%' . $param . '%')
                ->orwhere('asin', 'like', '%' . $param . '%')
                ->groupBy('asin')
                ->orderBy('updated_at', 'desc')
                ->get(['asin'])->map(function ($value) {
                    return (array)$value;
                })->toArray();
            $asinList = [];
            $asinIdList = [];
            if (!empty($productList)) {
                foreach ($productList as $key => $value) {
                    $asinList[$value['id']] = $value['asin'];
                    $asinIdList[] = $value['id'];
                }
            }

            //查询跟卖数据
            $resellingidList = [];
            if (!empty($asinIdList)) {
                $resellingList = DB::connection('vlz')->table('tbl_reselling_asin')
                    ->select('id', 'asin', 'product_id')
                    ->whereIn('product_id', array_unique($asinIdList))
                    ->get()->map(function ($value) {
                        return (array)$value;
                    })->toArray();
            }
            if (!empty($resellingList)) {
                foreach ($resellingList as $rlk => $rlv) {
                    $resellingidList[] = $rlv['id'];
                }
                //查询对应的asin 下面 跟卖数量
                $taskList = DB::connection('vlz')->table('tbl_reselling_task')
                    ->select('id', 'reselling_num', 'reselling_time', 'created_at', 'reselling_asin_id')
                    ->whereIn('reselling_asin_id', array_unique($resellingidList))
                    ->groupBy('reselling_asin_id')
                    ->orderBy('reselling_time', 'desc')
                    ->get()->map(function ($value) {
                        return (array)$value;
                    })->toArray();
                foreach ($resellingList as $rltk => $rltv) {
                    foreach ($taskList as $tlk => $tlv) {
                        if ($rltv['id'] == $tlv['reselling_asin_id']) {
                            $resellingList[$rltk]['reselling_num'] = $tlv['reselling_num'];
                            $resellingList[$rltk]['reselling_time'] = $tlv['reselling_time'];
                        }
                    }
                }
            }


            //中间对应关系数据
            $sap_asin_match_sku = DB::connection('vlz')->table('sap_asin_match_sku')
                ->select('sap_seller_id', 'asin', 'sap_seller_bg', 'sap_seller_bu', 'id', 'status', 'updated_at', 'sku_status', 'sku')
                ->whereIn('asin', array_unique($asinList))
                ->get()->map(function ($value) {
                    return (array)$value;
                })->toArray();
            $sap_seller_id_list = [];
            if (!empty($sap_asin_match_sku)) {
                foreach ($sap_asin_match_sku as $k => $v) {
                    if (!in_array($v['sap_seller_id'], $sap_seller_id_list)) {
                        $sap_seller_id_list[$v['sap_seller_id']]['asin'] = $v['asin'];
                        $sap_seller_id_list[$v['sap_seller_id']]['BG'] = $v['sap_seller_bg'];
                        $sap_seller_id_list[$v['sap_seller_id']]['BU'] = $v['sap_seller_bu'];
                        $sap_seller_id_list[$v['sap_seller_id']]['sku'] = $v['sku'];
                        $sap_seller_id_list[$v['sap_seller_id']]['sap_updated_at'] = $v['updated_at'];
                        $sap_seller_id_list[$v['sap_seller_id']]['sku_status'] = $v['sku_status'];

                    }
                }
            }
            $userList = DB::table('users')->select('id', 'name', 'email', 'sap_seller_id')->whereIn('sap_seller_id', array_keys($sap_seller_id_list))->get()->map(function ($value) {
                return (array)$value;
            })->toArray();
            if (!empty($userList)) {
                foreach ($userList as $uk => $uv) {
                    foreach ($sap_seller_id_list as $sk => $sv) {
                        if ($uv['sap_seller_id'] == $sk) {
                            $userList[$uk]['asin'] = $sv['asin'];
                            $userList[$uk]['BG'] = $sv['BG'];
                            $userList[$uk]['BU'] = $sv['BU'];
                            $userList[$uk]['sku'] = $sv['sku'];
                            $userList[$uk]['sku_status'] = $sv['sku_status'];
                            $userList[$uk]['sap_updated_at'] = $sv['sap_updated_at'];
                        }
                    }

                }
            }
            //  $userList2 = User::whereIn('sap_seller_id', $sap_seller_id_list)->groupBy(['email'])->get()->toArray();
            foreach ($productList as $pk => $pv) {
                foreach ($userList as $ulk => $ulv) {
                    if ($pv['asin'] == $ulv['asin']) {
                        $productList[$pk]['userName'] = $ulv['name'];
                        $productList[$pk]['email'] = $ulv['email'];
                        $productList[$pk]['BG'] = $ulv['BG'];
                        $productList[$pk]['BU'] = $ulv['BU'];
                        $productList[$pk]['sku'] = $ulv['sku'];
                        $productList[$pk]['sku_status'] = $ulv['sku_status'];
                        $productList[$pk]['sap_updated_at'] = $ulv['sap_updated_at'];
                    } else {
                        $productList[$pk]['userName'] = '';
                        $productList[$pk]['email'] = '';
                        $productList[$pk]['BG'] = '';
                        $productList[$pk]['BU'] = '';
                        $productList[$pk]['sku'] = '';
                        $productList[$pk]['sku_status'] = '';
                        $productList[$pk]['sap_updated_at'] = '';
                    }
                }
                foreach ($resellingList as $resk => $resv) {
                    if ($pv['id'] == $resv['product_id']) {
                        $productList[$pk]['reselling_num'] = @$resv['reselling_num'];
                        $productList[$pk]['reselling_time'] = @$resv['reselling_time'];
                    }
                }
            }


            //==================== 查询 like sku ===================================//
            $asinList = [];
            //中间对应关系数据
            $sap_asin_match_sku2 = DB::connection('vlz')->table('sap_asin_match_sku')
                ->select('sap_seller_id', 'asin', 'sap_seller_bg', 'sap_seller_bu', 'id', 'status', 'updated_at', 'sku_status', 'sku')
                ->where('sku', 'like', '%' . $param . '%')
                ->get()->map(function ($value) {
                    return (array)$value;
                })->toArray();
            $sap_seller_id_list2 = [];
            if (!empty($sap_asin_match_sku2)) {
                foreach ($sap_asin_match_sku2 as $k => $v) {
                    $asinList[$v['id']] = $v['asin'];
                    if (!in_array($v['sap_seller_id'], $sap_seller_id_list2)) {
                        $sap_seller_id_list2[$v['sap_seller_id']]['asin'] = $v['asin'];
                        $sap_seller_id_list2[$v['sap_seller_id']]['BG'] = $v['sap_seller_bg'];
                        $sap_seller_id_list2[$v['sap_seller_id']]['BU'] = $v['sap_seller_bu'];
                        $sap_seller_id_list2[$v['sap_seller_id']]['sku'] = $v['sku'];
                        $sap_seller_id_list2[$v['sap_seller_id']]['sap_updated_at'] = $v['updated_at'];
                        $sap_seller_id_list2[$v['sap_seller_id']]['sku_status'] = $v['sku_status'];

                    }
                }
                $productList2 = DB::connection('vlz')->table('asins')
                    ->select('id', 'asin', 'images', 'marketplaceid', 'title', 'images', 'listed_at', 'mpn', 'seller_count', 'updated_at', 'reselling_switch')
                    ->whereNotNull('title')
                    ->whereIn('asin', array_unique($asinList))
                    ->groupBy('asin')
                    ->orderBy('updated_at', 'desc')
                    ->get(['asin'])->map(function ($value) {
                        return (array)$value;
                    })->toArray();

                if (!empty($productList2)) {
                    foreach ($productList2 as $key => $value) {
                        $asinList[$value['id']] = $value['asin'];
                        $asinIdList[] = $value['id'];
                    }
                }

                //查询跟卖数据
                $resellingidList = [];
                $resellingList = DB::connection('vlz')->table('tbl_reselling_asin')
                    ->select('id', 'asin', 'product_id')
                    ->whereIn('product_id', array_unique($asinIdList))
                    ->get()->map(function ($value) {
                        return (array)$value;
                    })->toArray();
                if (!empty($resellingList)) {
                    foreach ($resellingList as $rlk => $rlv) {
                        $resellingidList[] = $rlv['id'];
                    }
                    //查询对应的asin 下面 跟卖数量
                    $taskList = DB::connection('vlz')->table('tbl_reselling_task')
                        ->select('id', 'reselling_num', 'reselling_time', 'created_at', 'reselling_asin_id')
                        ->whereIn('reselling_asin_id', array_unique($resellingidList))
                        ->groupBy('reselling_asin_id')
                        ->orderBy('reselling_time', 'desc')
                        ->get()->map(function ($value) {
                            return (array)$value;
                        })->toArray();
                    foreach ($resellingList as $rltk => $rltv) {
                        foreach ($taskList as $tlk => $tlv) {
                            if ($rltv['id'] == $tlv['reselling_asin_id']) {
                                $resellingList[$rltk]['reselling_num'] = $tlv['reselling_num'];
                                $resellingList[$rltk]['reselling_time'] = $tlv['reselling_time'];
                            }
                        }
                    }
                }


                $userList2 = DB::table('users')->select('id', 'name', 'email', 'sap_seller_id')->whereIn('sap_seller_id', array_keys($sap_seller_id_list2))->get()->map(function ($value) {
                    return (array)$value;
                })->toArray();
                if (!empty($userList2)) {
                    foreach ($userList2 as $uk => $uv) {
                        foreach ($sap_seller_id_list2 as $sk => $sv) {
                            if ($uv['sap_seller_id'] == $sk) {
                                $userList2[$uk]['asin'] = $sv['asin'];
                                $userList2[$uk]['BG'] = $sv['BG'];
                                $userList2[$uk]['BU'] = $sv['BU'];
                                $userList2[$uk]['sku'] = $sv['sku'];
                                $userList2[$uk]['sku_status'] = $sv['sku_status'];
                                $userList2[$uk]['sap_updated_at'] = $sv['sap_updated_at'];
                            }
                        }

                    }
                }//  $userList2 = User::whereIn('sap_seller_id', $sap_seller_id_list2)->groupBy(['email'])->get()->toArray();
                foreach ($productList2 as $pk => $pv) {
                    foreach ($userList2 as $ulk => $ulv) {
                        if ($pv['asin'] == $ulv['asin']) {
                            $productList2[$pk]['userName'] = $ulv['name'];
                            $productList2[$pk]['email'] = $ulv['email'];
                            $productList2[$pk]['BG'] = $ulv['BG'];
                            $productList2[$pk]['BU'] = $ulv['BU'];
                            $productList2[$pk]['sku'] = $ulv['sku'];
                            $productList2[$pk]['sku_status'] = $ulv['sku_status'];
                            $productList2[$pk]['sap_updated_at'] = $ulv['sap_updated_at'];
                        } else {
                            $productList2[$pk]['userName'] = '';
                            $productList2[$pk]['email'] = '';
                            $productList2[$pk]['BG'] = '';
                            $productList2[$pk]['BU'] = '';
                            $productList2[$pk]['sku'] = '';
                            $productList2[$pk]['sku_status'] = '';
                            $productList2[$pk]['sap_updated_at'] = '';
                        }
                    }
                    foreach ($resellingList as $resk => $resv) {
                        if ($pv['id'] == $resv['product_id']) {
                            $productList2[$pk]['reselling_num'] = $resv['reselling_num'];
                            $productList2[$pk]['reselling_time'] = $resv['reselling_time'];
                        }
                    }
                }
            } else {
                $productList2 = [];
                $userList2 = [];
            }


            $returnDate['userList'] = array_merge($userList, $userList2);
            $returnDate['productList'] = array_merge($productList2, $productList);
        }

        return $returnDate;

    }

    /**
     * 修改 开启关闭
     * @param Request $request
     * @return string
     */
    public function updateAsinSta(Request $request)
    {
        $DOMIN_MARKETPLACEID_URL = Asin::DOMIN_MARKETPLACEID_URL;
        if (!empty($_POST['id'])) {
            $toup = 0;
            if (@$_POST['reselling_switch'] == 1) {
                $toup = 1;
            }
            $arr_id = explode(',', $_POST['id']);
            $result = DB::connection('vlz')->table('asins')
                ->whereIn('id', $arr_id)
                ->update(['reselling_switch' => $toup]);
            $asinOne = DB::connection('vlz')->table('asins')
                ->select('id', 'asin', 'marketplaceid', 'listed_at')
                ->whereIn('id', $arr_id)
                ->get()->map(function ($value) {
                    return (array)$value;
                })->toArray();

            if ($result > 0) {
                if ($toup == 1) {
                    //防止添加重复数据，所以先删除后增加
                    DB::connection('vlz')->table('tbl_reselling_asin')->whereIn('product_id', $arr_id)->delete();//删除1条
                    foreach ($asinOne as $k => $v) {
                        $data = [
                            'product_id' => $v['id'],
                            'domain' => $DOMIN_MARKETPLACEID_URL[$v['marketplaceid']],
                            'asin' => $v['asin'],
                            'reselling' => 1
                        ];
                        //新增tbl_reselling_asin
                        DB::connection('vlz')->table('tbl_reselling_asin')->insert($data);
                    }
                } else {
                    //防止添加重复数据，所以先删除后增加
                    DB::connection('vlz')->table('tbl_reselling_asin')->whereIn('product_id', $arr_id)->delete();//删除1条
                }
                $r_message=['status'=>1,'msg'=>'更新成功'];
            } else {
                $r_message=['status'=>0,'msg'=>'更新失败'];
            }
        } else {
            $r_message=['status'=>0,'msg'=>'缺少参数'];
        }
        return $r_message;
        exit;
    }

    /**
     * @param Request $request
     * 批量更新跟卖数据
     */
    public function updateAsinStaAll(Request $request)
    {
        $asinsIdList = [];
        $DOMIN_MARKETPLACEID_URL = Asin::DOMIN_MARKETPLACEID_URL;
        $asinsList = DB::connection('vlz')->table('asins')
            ->select('id')
            ->where('reselling_switch', 0)
            ->limit(30)
            ->get()
            ->map(function ($value) {
                return (array)$value;
            })->toArray();
        if (!empty($asinsList)) {
            foreach ($asinsList as $key => $value) {
                $asinsIdList[] = $value['id'];
            }
        }

        $toup = 1;  //TODO 0
        $arr_id = array_unique($asinsIdList);// explode(',', $_POST['id']); //todo 改回 arr_id
        $result = DB::connection('vlz')->table('asins')
            ->whereIn('id', $arr_id)
            ->update(['reselling_switch' => $toup]);

        $asinOne = DB::connection('vlz')->table('asins')
            ->select('id', 'asin', 'marketplaceid', 'listed_at')
            ->whereIn('id', $arr_id)
            ->get()->map(function ($value) {
                return (array)$value;
            })->toArray();

        if ($result > 0) {
            echo '更新成功';
            if ($toup == 1) {
                //防止添加重复数据，所以先删除后增加
                DB::connection('vlz')->table('tbl_reselling_asin')->whereIn('product_id', $arr_id)->delete();//删除1条
                foreach ($asinOne as $k => $v) {
                    $data = [
                        'product_id' => $v['id'],
                        'domain' => $DOMIN_MARKETPLACEID_URL[$v['marketplaceid']],
                        'asin' => $v['asin'],
                        'reselling' => 1
                    ];
                    //新增tbl_reselling_asin
                    DB::connection('vlz')->table('tbl_reselling_asin')->insert($data);
                }
            } else {
                //防止添加重复数据，所以先删除后增加
                DB::connection('vlz')->table('tbl_reselling_asin')->whereIn('product_id', $arr_id)->delete();//删除1条
            }

        } else {
            echo '更新失败';
        }
        exit;
    }

    /**
     * 数据导出
     * @param Request $request
     */
    public function hijackExport(Request $request)
    {
        header('Access-Control-Allow-Origin:*');
        $DOMIN_MARKETPLACEID = Asin::DOMIN_MARKETPLACEID;
        //得到登录用户信息
        // $user = Auth::user()->toArray();
        if ($request) {
            //   echo $request->params;
        }
        $idList = isset($request['idList']) ? $request['idList'] : '';
        if (!empty($request['startTime'] && !empty($request['endTime']))) {

            //查询跟卖数据 根据开始时间 结束时间
            $startTime = $request['startTime'];
            $endTime = $request['endTime'];
            $resellingidList = [];
            $r_asin_id_l = [];//对应asinid 数组
            $productIdList = [];
            //查询对应的asin 下面 跟卖数量
            $taskList = DB::connection('vlz')->table('tbl_reselling_task')
                ->select('id', 'reselling_num', 'reselling_time', 'created_at', 'reselling_asin_id')
                ->where('reselling_time', '>=', $startTime)
                ->where('reselling_time', '<=', $endTime)
                ->groupBy('reselling_asin_id')
                ->orderBy('reselling_time', 'desc')
                ->get()->map(function ($value) {
                    return (array)$value;
                })->toArray();
            foreach ($taskList as $tlk => $tlv) {
                $r_asin_id_l[] = $tlv['reselling_asin_id'];
            }

            $resellingList = DB::connection('vlz')->table('tbl_reselling_asin')
                ->select('id', 'asin', 'product_id')
                ->whereIn('id', array_unique($r_asin_id_l))
                ->get()->map(function ($value) {
                    return (array)$value;
                })->toArray();

            if (!empty($resellingList)) {
                foreach ($resellingList as $rlk => $rlv) {
                    $productIdList[] = $rlv['product_id'];
                    $resellingidList[$rlv['id']] = $rlv['product_id'];
                }
                foreach ($taskList as $tlk => $tlv) {
                    foreach ($resellingList as $rltk => $rltv) {
                        if ($rltv['id'] == $tlv['reselling_asin_id']) {
                            $resellingList[$rltk]['reselling_num'] = $tlv['reselling_num'];
                            $resellingList[$rltk]['reselling_time'] = $tlv['reselling_time'];
                        }
                    }
                }
            }

        }
        echo
            'ASIN,' .
            'Marketplace,' .
            'SKU,' .
            'Seller,' .
            'Notes,' .
            'Title,' .
            'Seller ID,' .
            'Seller Name,' .
            'Price,' .
            'Shipping,' .
            'Date,' .
            'Duration' . "\r\n" . "\r\n";
        //查询用户列表
        $users = User::select('name', 'email')->where('locked', '=', '0')->get()->toArray();
        //查询所有 asin 信息
        if ($idList == '' || empty($idList)) {
            $idList = array_unique($productIdList);
        } else {
            $idList = explode(',', $idList);
        }
        $productList = DB::connection('vlz')->table('asins')
            ->select('id', 'asin', 'images', 'marketplaceid', 'title', 'images', 'listed_at', 'mpn', 'seller_count', 'updated_at', 'reselling_switch')
            ->whereNotNull('title')
            ->whereIn('id', $idList)
            ->groupBy('asin')
            ->orderBy('updated_at', 'desc')
            ->get(['asin'])->map(function ($value) {
                return (array)$value;
            })->toArray();
        $asinList = [];
        //var_dump($productList);exit;
        if (!empty($productList)) {
            foreach ($productList as $key => $value) {
                $asinList[$value['id']] = $value['asin'];
                $asinIdList[] = $value['id'];
            }
        }


        //中间对应关系数据
        $sap_asin_match_sku = DB::connection('vlz')->table('sap_asin_match_sku')
            ->select('sap_seller_id', 'asin', 'sap_seller_bg', 'sap_seller_bu', 'id', 'status', 'updated_at', 'sku_status', 'sku')
            ->whereIn('asin', array_unique($asinList))
            ->get()->map(function ($value) {
                return (array)$value;
            })->toArray();
        $sap_seller_id_list = [];
        if (!empty($sap_asin_match_sku)) {
            foreach ($sap_asin_match_sku as $k => $v) {
                if (!in_array($v['sap_seller_id'], $sap_seller_id_list)) {
                    $sap_seller_id_list[$v['sap_seller_id']]['asin'] = $v['asin'];
                    $sap_seller_id_list[$v['sap_seller_id']]['BG'] = $v['sap_seller_bg'];
                    $sap_seller_id_list[$v['sap_seller_id']]['BU'] = $v['sap_seller_bu'];
                    $sap_seller_id_list[$v['sap_seller_id']]['sku'] = $v['sku'];
                    $sap_seller_id_list[$v['sap_seller_id']]['sap_updated_at'] = $v['updated_at'];
                    $sap_seller_id_list[$v['sap_seller_id']]['sku_status'] = $v['sku_status'];

                }
            }
        }
        $userList = DB::table('users')->select('id', 'name', 'email', 'sap_seller_id')->whereIn('sap_seller_id', array_keys($sap_seller_id_list))->get()->map(function ($value) {
            return (array)$value;
        })->toArray();
        if (!empty($userList)) {
            foreach ($userList as $uk => $uv) {
                foreach ($sap_seller_id_list as $sk => $sv) {
                    if ($uv['sap_seller_id'] == $sk) {
                        $userList[$uk]['asin'] = $sv['asin'];
                        $userList[$uk]['BG'] = $sv['BG'];
                        $userList[$uk]['BU'] = $sv['BU'];
                        $userList[$uk]['sku'] = $sv['sku'];
                        $userList[$uk]['sku_status'] = $sv['sku_status'];
                        $userList[$uk]['sap_updated_at'] = $sv['sap_updated_at'];
                    }
                }

            }
        }
        //  $userList2 = User::whereIn('sap_seller_id', $sap_seller_id_list)->groupBy(['email'])->get()->toArray();
        foreach ($productList as $pk => $pv) {
            foreach ($userList as $ulk => $ulv) {
                if ($pv['asin'] == $ulv['asin']) {
                    $productList[$pk]['userName'] = $ulv['name'];
                    $productList[$pk]['email'] = $ulv['email'];
                    $productList[$pk]['BG'] = $ulv['BG'];
                    $productList[$pk]['BU'] = $ulv['BU'];
                    $productList[$pk]['sku'] = $ulv['sku'];
                    $productList[$pk]['sku_status'] = $ulv['sku_status'];
                    $productList[$pk]['sap_updated_at'] = $ulv['sap_updated_at'];
                } else {
                    $productList[$pk]['userName'] = '';
                    $productList[$pk]['email'] = '';
                    $productList[$pk]['BG'] = '';
                    $productList[$pk]['BU'] = '';
                    $productList[$pk]['sku'] = '';
                    $productList[$pk]['sku_status'] = '';
                    $productList[$pk]['sap_updated_at'] = '';
                }
            }
            foreach ($resellingList as $resk => $resv) {
                if ($pv['id'] == $resv['product_id']) {
                    $productList[$pk]['reselling_num'] = $resv['reselling_num'];
                    $productList[$pk]['reselling_time'] = $resv['reselling_time'];
                } else {
                    $productList[$pk]['reselling_num'] = '';
                    $productList[$pk]['reselling_time'] = '';
                }
            }
        }
        /** 查询跟卖信息*/
        if (!empty($resellingList) && !empty($resellingidList)) {
            $taskIdList = [];
            $taskList = DB::connection('vlz')->table('tbl_reselling_task')
                ->select('id', 'reselling_asin_id', 'reselling_num', 'reselling_time', 'created_at')
                ->whereIn('reselling_asin_id', array_unique(array_keys($resellingidList)))
                ->get()->map(function ($value) {
                    return (array)$value;
                })->toArray();
            if (!empty($taskList)) {
                foreach ($taskList as $tlK => $tlv) {
                    $taskIdList[] = $tlv['id'];
                }

                /** 查询detail **/
                $taskDetail = DB::connection('vlz')->table('tbl_reselling_detail')
                    ->select('id', 'task_id', 'price', 'shipping_fee', 'account', 'white', 'sellerid', 'created_at', 'reselling_remark')
                    ->whereIn('task_id', array_unique($taskIdList))
                    ->get()->map(function ($value) {
                        return (array)$value;
                    })->toArray();
                if (!empty($taskDetail)) {
                    foreach ($taskDetail as $tk => $tv) {
                        $timecount = 0;
                        $uptime = 0;
                        foreach ($taskDetail as $k => $v) {
                            if (count($taskDetail) == 1) {
                                $timecount = 1;
                            } else {
                                if ($v['task_id'] == $tv['task_id']) {
                                    if ($v['created_at'] > 0) {
                                        if ($v['created_at'] - $uptime > 3600 && $timecount > 0) {
                                            $uptime = $v['created_at'];
                                            $timecount = 1;
                                        } else if ($v['created_at'] - $uptime > 3600 && $timecount == 0) {
                                            $uptime = $v['created_at'];
                                            $timecount = 1;
                                        } else {
                                            $timecount++;
                                        }
                                    }

                                }

                            }
                            $taskDetail[$tk]['timecount'] = $timecount;
                        }
                        foreach ($taskList as $taK => $tav) {
                            if ($tv['task_id'] == $tav['id']) {
                                $taskDetail[$tk]['product_id'] = $tav['reselling_asin_id'];
                            }
                        }
                    }

                    foreach ($taskDetail as $tdkey => $tdval) {
                        foreach ($productList as $pkey => $pval) {
                            if ($tdval['product_id'] == $pval['id']) {
                                $taskDetail[$tdkey]['title'] = $pval['title'];
                                $taskDetail[$tdkey]['asin'] = $pval['asin'];
                                $taskDetail[$tdkey]['sku'] = $pval['sku'];
                                $taskDetail[$tdkey]['userName'] = $pval['userName'];
                                $taskDetail[$tdkey]['marketplaceid'] = $DOMIN_MARKETPLACEID[$pval['marketplaceid']];
                            }
                        }
                    }

                    if (!empty($taskDetail)) {
                        foreach ($taskDetail as $key => $dv) {
                            if (!empty($dv['asin'])) {
                                echo '"' . @$dv['asin'] . '",' .
                                    '"' . @$dv['marketplaceid'] . '",' .
                                    '"' . @$dv['sku'] . '",' .
                                    '"' . @$dv['userName'] . '",' .
                                    '"' . @$dv['reselling_remark'] . '",' .
                                    '"' . @$dv['title'] . '",' .
                                    '"' . @$dv['sellerid'] . '",' .
                                    '"' . @$dv['account'] . '",' .
                                    '"' . @$dv['price'] . '",' .
                                    '"' . @$dv['shipping_fee'] . '",' .
                                    '"' . date('Y-m-d H:i', @$dv['created_at']) . '",' .
                                    '"' . @$dv['timecount'] . '"' .
                                    "\r\n";
                            }
                        }
                    }

                }
                /** detail * end*/
            }
        }
        /**  查询根脉信息  END  **/
        exit;
    }

    /**
     * 左侧跟卖列表 顶部产品信息
     */
    public function resellingList(Request $request)
    {
        $DOMIN_MARKETPLACEID = Asin::DOMIN_MARKETPLACEID;
        $DOMIN_MARKETPLACEID_SX = Asin::DOMIN_MARKETPLACEID_SX;
        //查询跟卖数据 根据开始时间 结束时间
        $startTime = isset($request['startTime']) ? $request['startTime'] : 0;
        $endTime = isset($request['endTime']) ? $request['endTime'] : 0;
        //根据ID 查询asins 信息
        if ($request['id']) {
            $asins = DB::connection('vlz')->table('asins')
                ->select('id', 'asin', 'images', 'marketplaceid', 'title', 'images', 'listed_at', 'mpn', 'updated_at')
                ->where('id', $request['id'])
                ->get()->map(function ($value) {
                    return (array)$value;
                })->toArray();
            $taskList = [];
            if (!empty($asins)) {
                $as = $asins[0];
                $asin = $asins[0]['asin'];
                $asinId = $asins[0]['id'];
                $sku = '';
                $sku_status = '';
                $domainUrl = $DOMIN_MARKETPLACEID[isset($as['marketplaceid']) ? $as['marketplaceid'] : ''];
                //中间对应关系数据
                $sap_asin_match_sku = DB::connection('vlz')->table('sap_asin_match_sku')
                    ->select('sap_seller_id', 'asin', 'sap_seller_bg', 'sap_seller_bu', 'id', 'status', 'updated_at', 'sku_status', 'sku')
                    ->where('asin', $asin)
                    ->groupBy('asin')
                    ->get()->map(function ($value) {
                        return (array)$value;
                    })->toArray();
                $sap_seller_id_list = [];
                $sellerId = 0;
                $user_name = '';
                if (!empty($sap_asin_match_sku)) {
                    foreach ($sap_asin_match_sku as $k => $v) {
                        if (!in_array($v['sap_seller_id'], $sap_seller_id_list)) {
                            $sap_seller_id_list[$v['sap_seller_id']]['asin'] = $v['asin'];
                            $sap_seller_id_list[$v['sap_seller_id']]['sku'] = $v['sku'];
                            $sap_seller_id_list[$v['sap_seller_id']]['sku_status'] = $v['sku_status'];
                            $sku = $v['sku'];
                            $sku_status = $v['sku_status'];
                            $sellerId = $v['sap_seller_id'];
                        }
                    }
                }
                if ($sellerId > 0) {
                    $userList = DB::table('users')->select('id', 'name', 'email', 'sap_seller_id')
                        ->where('sap_seller_id', $sellerId)
                        ->get()->map(function ($value) {
                            return (array)$value;
                        })->toArray();
                    if (!empty($userList)) {
                        $user_name = $userList[0]['name'];
                    }
                }


                if (!empty($domainUrl) && !empty($asin)) {
                    $asins[0]['domin_sx'] = $DOMIN_MARKETPLACEID_SX[isset($asins[0]['marketplaceid']) ? $asins[0]['marketplaceid'] : ''];
                    $resellingList = DB::connection('vlz')->table('tbl_reselling_asin')
                        ->select('reselling_num', 'updated_at', 'created_at', 'reselling_remark', 'id', 'asin')
                        ->where('asin', $asin)
                        ->where('product_id', $as['id'])
                        //  ->where('domain', $domainUrl)
                        ->get()->map(function ($value) {
                            return (array)$value;
                        })->toArray();
                    if (!empty($resellingList)) {
                        $reselling_asin_id_l = [];
                        foreach ($resellingList as $rlkey => $rlvalue) {
                            $reselling_asin_id_l[] = $rlvalue['id'];
                        }
                        if (!empty($reselling_asin_id_l)) {
                            //查询跟卖数据
                            //时间范围 筛选条件
                            if ($startTime > 0 && $endTime > 0) {
                                $taskList = DB::connection('vlz')->table('tbl_reselling_task')
                                    ->select('id', 'reselling_num', 'reselling_time', 'created_at')
                                    ->whereIn('reselling_asin_id', array_unique($reselling_asin_id_l))
                                    ->where('reselling_time', '>=', $startTime)
                                    ->where('reselling_time', '<=', $endTime)
                                    ->orderBy('reselling_time', 'desc')
                                    ->get()->map(function ($value) {
                                        return (array)$value;
                                    })->toArray();

                            } else {
                                //没有时间范围 正常查询
                                $taskList = DB::connection('vlz')->table('tbl_reselling_task')
                                    ->select('id', 'reselling_num', 'reselling_time', 'created_at')
                                    ->whereIn('reselling_asin_id', array_unique($reselling_asin_id_l))
                                    ->orderBy('reselling_time', 'desc')
                                    ->get()->map(function ($value) {
                                        return (array)$value;
                                    })->toArray();
                            }
                            if (!empty($taskList)) {

                                $asins[0]['asin_reselling_num'] = $taskList[0]['reselling_num'];
                                $asins[0]['asin_reselling_time'] = date('Y/m/d H:i:s', $taskList[0]['reselling_time']);
                                $asins[0]['sku'] = $sku;
                                $asins[0]['sku_status'] = $sku_status;
                                $asins[0]['user_name'] = $user_name;

                                foreach ($taskList as $tk => $tv) {
                                    $taskList[$tk]['reselling_time'] = date('Y/m/d H:i:s', $tv['reselling_time']);
                                }
                            }
                        }
                    }
                }

            }
            return [$asins, $taskList];
        }

    }

    /**
     * 跟卖detail
     */
    public function resellingDetail_old(Request $request)
    {
        $taskId = $request['taskId'];
        $taskIdList = [];
        $uptime = 0;
        $timecount = 0;
        $taskDetail = [];
        if ($taskId > 0) {
            $taskDetail = DB::connection('vlz')->table('tbl_reselling_detail')
                ->select('id', 'price', 'task_id', 'shipping_fee', 'account', 'white', 'sellerid', 'created_at', 'reselling_remark')
                ->where('task_id', $taskId)
                ->where('white', 0)
                ->get()->map(function ($value) {
                    return (array)$value;
                })->toArray();
            //查询每个task 时间
            $taskList = DB::connection('vlz')->table('tbl_reselling_task')
                ->select('id', 'reselling_num', 'reselling_time')
                ->groupBy('reselling_asin_id')
                ->orderBy('reselling_time', 'desc')
                ->get()->map(function ($value) {
                    return (array)$value;
                })->toArray();
            if (!empty($taskList)) {
                foreach ($taskList as $tk => $tv) {

                }
            }
            if (!empty($taskDetail)) {
                foreach ($taskDetail as $k => $v) {
                    $taskDetail[$k]['timecount'] = $timecount;
                    $taskDetail[$k]['price'] = $v['price'] / 100;
                }
            }
        }
        return $taskDetail;

    }

    public function resellingDetail(Request $request)
    {
        $taskId = $request['taskId'];
        $taskIdList = [];
        $reselling_asin_id = [];
        $timecount = [];
        $taskDetail = [];
        $reselling_count_list = [];
        if ($taskId > 0) {
            $taskDetail = DB::connection('vlz')->table('tbl_reselling_detail')
                ->select('id', 'price', 'task_id', 'shipping_fee', 'account', 'white', 'sellerid', 'created_at', 'reselling_remark')
                ->where('task_id', $taskId)
                ->where('white', 0)
                ->get()->map(function ($value) {
                    return (array)$value;
                })->toArray();
            $reselling_one = DB::connection('vlz')->table('tbl_reselling_task')
                ->select('created_at', 'id', 'reselling_asin_id')
                ->where('id', $taskId)
                ->get()->map(function ($value) {
                    return (array)$value;
                })->toArray();
            $asin_g = DB::connection('vlz')->table('tbl_reselling_asin')
                ->select('created_at', 'id', 'product_id')
                ->where('id', $reselling_one[0]['reselling_asin_id'])
                ->get()->map(function ($value) {
                    return (array)$value;
                })->toArray();
            if (!empty($asin_g)) {
                //查询每个task 时间
                $asins = DB::connection('vlz')->table('asins')
                    ->select('id', 'asin')
                    ->where('id', $asin_g[0]['product_id'])
                    ->get()->map(function ($value) {
                        return (array)$value;
                    })->toArray();
                if (!empty($asins)) {
                    $resellingList = DB::connection('vlz')->table('tbl_reselling_asin')
                        ->select('created_at', 'id')
                        ->where('asin', $asins[0]['asin'])
                        ->where('product_id', $asins[0]['id'])
                        ->get()->map(function ($value) {
                            return (array)$value;
                        })->toArray();
                    if (!empty($resellingList)) {
                        foreach ($resellingList as $rlkey => $rlvalue) {
                            $reselling_asin_id[] = $rlvalue['id'];
                        }
                    }
                }
            }

            if (!empty($reselling_asin_id)) {
                $taskList = DB::connection('vlz')->table('tbl_reselling_task')
                    ->select('id', 'reselling_time')
                    ->whereIn('reselling_asin_id', $reselling_asin_id)
                    ->orderBy('reselling_time', 'desc')
                    ->get()->map(function ($value) {
                        return (array)$value;
                    })->toArray();
                if (!empty($taskList)) {
                    foreach ($taskList as $tk => $tv) {
                        $taskIdList[] = $tv['id'];
                    }
                    if (!empty($taskList)) {
                        $taskDetail_list = DB::connection('vlz')->table('tbl_reselling_detail')
                            ->select('id', 'task_id', 'sellerid', 'created_at')
                            ->whereIn('task_id', $taskIdList)
                            ->where('white', 0)
                            ->get()->map(function ($value) {
                                return (array)$value;
                            })->toArray();
                        if (!empty($taskDetail_list)) {
                            foreach ($taskDetail_list as $tlk => $tlv) {
                                $taskDetail_list[$tlk]['count'] = 0;
                                $created_at = 0;
                                $reselling_count = 0;
                                foreach ($taskDetail_list as $tk => $tv) {
                                    if ($tlv['sellerid'] == $tv['sellerid']) {
                                        //  echo $tv['id'].'---'. $tv['task_id'] . '-----' . date('Y/m/d H:i:s', $created_at) . '--';
                                        if ($tv['created_at'] - $created_at > 3600 && $reselling_count == 0) {
                                        } elseif ($tv['created_at'] - $created_at > 3600) {
                                        } elseif ($tv['created_at'] - $created_at < 3600) {
                                            $reselling_count++;
                                        }
                                        $created_at = $tv['created_at'];
                                    }
                                }
                                $taskDetail_list[$tlk]['count'] = $reselling_count;
                                $reselling_count_list[$tlv['sellerid']] = $reselling_count;
                            }

                        }
                    }
                }
            }
            if (!empty($taskDetail)) {
                foreach ($taskDetail as $k => $v) {
                    $taskDetail[$k]['timecount'] = $reselling_count_list[$v['sellerid']];
                    $taskDetail[$k]['price'] = $v['price'] / 100;
                    $taskDetail[$k]['shipping_fee'] = $v['shipping_fee'] / 100;

                }
            }
        }
        return $taskDetail;

    }

    /**
     * 修改 detail remark  备注
     * @param Request $request
     */
    public function upResellingDetail(Request $request)
    {
        if (!empty($request['id']) && $request['id'] > 0 && $request['remark']) {
            $result = DB::connection('vlz')->table('tbl_reselling_detail')
                ->where('id', $request['id'])
                ->update(['reselling_remark' => $request['remark']]);
            if ($result > 0) {
                echo '更新成功';
            } else {
                echo '更新失败';
            }
        } else {
            echo '缺少参数';
        }
        exit;
    }

}