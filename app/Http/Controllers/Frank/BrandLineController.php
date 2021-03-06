<?php
/**
 * Created by PhpStorm.
 * Date: 18.9.12
 * Time: 17:53
 */

namespace App\Http\Controllers\Frank;

use App\Asin;
use App\Models\KmsUserManual;
use App\Models\KmsVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\DB;

class BrandLineController extends Controller {

    use \App\Traits\Mysqli;
    use \App\Traits\DataTables;

    /**
     * @throws \App\Traits\MysqliException
     */
    public function index() {
        // print_r(array_keys($GLOBALS));
		if(!Auth::user()->can(['product-guide-show'])) die('Permission denied -- product-guide-show');
        $rows = $this->queryRows('SELECT item_group,brand,GROUP_CONCAT(DISTINCT item_model) AS item_models FROM asin GROUP BY item_group,brand');

        $itemGroupBrandModels = array();
        foreach ($rows as $row) {
            $itemGroupBrandModels[$row['item_group']][$row['brand']] = explode(',', $row['item_models']);
        }

        return view('frank/kmsBrandLine', compact('itemGroupBrandModels'));
    }

    /**
     * @throws \App\Traits\DataTablesException
     * @throws \App\Traits\MysqliException
     */
    public function get(Request $req) {
		if(!Auth::user()->can(['product-guide-show'])) die('Permission denied -- product-guide-show');
        $where = $this->dtWhere($req, ['item_group', 'item_model', 'item_no', 't1.asin', 'sellersku', 'brand', 'brand_line'], ['item_group' => 'item_group', 'brand' => 'brand', 'item_model' => 'item_model']);

        $orderby = $this->dtOrderBy($req);
        $limit = $this->dtLimit($req);

        $sql = "
        SELECT SQL_CALC_FOUND_ROWS
            t1.brand,
            t1.item_group,
            t1.item_model,
            ANY_VALUE(t1.brand_line) AS brand_line,
            t2.manualink,
            IF(ISNULL(t3.item_group), 0, 1) AS has_video,
            COUNT(t4.item_code) AS has_stock_info
        FROM asin t1
        LEFT JOIN (
            SELECT item_group,
            brand,
            item_model,
            any_value(link) manualink
            FROM kms_user_manual
            GROUP BY item_group,brand,item_model
        ) t2
        USING(item_group,brand,item_model)
        LEFT JOIN (
            SELECT DISTINCT item_group,brand,item_model FROM kms_video
        ) t3
        USING(item_group,brand,item_model)
        LEFT JOIN kms_stock t4
        ON t4.item_code=t1.item_no
        WHERE $where
        GROUP BY item_group,brand,item_model
        ORDER BY $orderby
        LIMIT $limit
        ";
        // $rows = DB::connection('frank')->table('asin')->select('item_group', 'item_model', 'brand')->groupBy('item_group', 'item_model')->get()->toArray();
        $rows = $this->queryRows($sql);
        foreach($rows as $key => $val){
        	if(empty($val['manualink'])){//manualink为空的时候
        		//item group,brand,model这三个参数都不为空的时候，就显示上传文件upload
				//brand,item_group,item_model
				$invalidModel = array('无型号');
				if($val['brand'] && $val['item_group'] && $val['item_model'] && !in_array($val['item_model'],$invalidModel) && Auth::user()->can(['user-manual-update'])){
					$rows[$key]['manualink'] = "<button  class='btn btn-success btn-xs btn-upload' data-brand='".$val['brand']."' data-group='".$val['item_group']."' data-model='".$val['item_model']."'>Upload</button>";
				}
			}else{
				$rows[$key]['manualink'] = "<a href='".$val['manualink']."' target='_blank' class='btn btn-success btn-xs'>View</a>";
			}
			$rows[$key]['manualink'] .= "<a href='/kms/usermanual?brand=".$val['brand']."&item_group=".$val['item_group']."&item_model=".$val['item_model']." target='_blank' class='btn btn-success btn-xs'>More</a>";

		}

        $total = $this->queryOne('SELECT FOUND_ROWS()');

        return ['data' => $rows, 'recordsTotal' => $total, 'recordsFiltered' => $total];
    }

    /**
     * @throws \App\Traits\MysqliException
     */
    public function getEmailDetailRightBar(Request $req) {

        $asinRows = $req->input('asinRows', []);

        if (empty($asinRows)) return ['manuals' => [], 'videos' => []];

        // $where = [];
        //
        // foreach ($asinRows as $row) {
        //
        //     // if (!preg_match('#^[\w.]+$#', $row['site'])) throw new DataInputException("Site - {$row['site']} format error.", 100);
        //     // if (!preg_match('#^\w+$#', $row['asin'])) throw new DataInputException("Asin - {$row['asin']} format error.", 100);
        //     // if (!preg_match('#^[\w-]+$#', $row['sellersku'])) throw new DataInputException("SellerSKU - {$row['sellersku']} format error.", 100);
        //
        //     foreach ($row as &$field) $field = addslashes($field);
        //
        //     $where[] = "(site='{$row['site']}' AND asin='{$row['asin']}' AND sellersku='{$row['sellersku']}')";
        // }
        //
        // $where = implode(' OR ', $where);
        //
        // $xxx = Asin::select('item_group', 'brand', 'item_model')->whereRaw($where)->orWhereRaw($where)->toSql();

        $modelRows = Asin::select('item_group', 'brand', 'item_model')->where(function ($where) use ($asinRows) {
            foreach ($asinRows as $row) {
                $where->orWhere(function ($where) use ($row) {
                    $where->where('site', $row['site']);
                    $where->where('asin', $row['asin']);
                    $where->where('sellersku', $row['sellersku']);
                });
            }
        })->get();

        if (empty($modelRows)) return ['manuals' => [], 'videos' => []];

        // 能不用 JOIN 就尽量不用
        // 有唯一索引，不用那么绕
        // WHERE 分组，传二维数组就可以
        $manuals = KmsUserManual::select('link')->where(function ($where) use ($modelRows) {
            foreach ($modelRows as $row) {
                $where->orWhere(function ($where) use ($row) {
                    $where->where('brand', $row->brand);
                    $where->where('item_group', $row->item_group);
                    $where->where('item_model', $row->item_model);
                });
            }
        })->get();

        $videos = KmsVideo::select('link')->where(function ($where) use ($modelRows) {
            // 注意 $modelRows 为空时，相当于不设条件！
            foreach ($modelRows as $row) {
                $where->orWhere(function ($where) use ($row) {
                    $where->where('brand', $row->brand);
                    $where->where('item_group', $row->item_group);
                    $where->where('item_model', $row->item_model);
                });
            }
        })->get();

        return compact('manuals', 'videos');
    }

}
