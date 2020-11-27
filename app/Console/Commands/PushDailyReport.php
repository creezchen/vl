<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Input;
use App\Asin;
use App\Starhistory;
use App\Skusweekdetails;
use PDO;
use DB;
use Log;

class PushDailyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:dailyReport {--date=} {--marketplace_id=} {--with_stock=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
		
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {	
        $date = $this->option('date');
        $marketplace_id = $this->option('marketplace_id');
        if($date >= date('Y-m-d') || !$date) $date = date('Y-m-d',strtotime('-1day'));
        if(!$marketplace_id) $marketplace_id = 'ATVPDKIKX0DER';

        $orderData = DB::connection('amazon')->table('order_items')
        ->leftJoin('orders',function($key){
            $key->on('order_items.seller_account_id', '=', 'orders.seller_account_id')
            ->on('order_items.amazon_order_id', '=', 'orders.amazon_order_id');
        })
        ->leftJoin('seller_accounts',function($key){
            $key->on('order_items.seller_account_id', '=', 'seller_accounts.id');
        })
        ->where('orders.order_status','<>','Canceled')
        ->where('seller_accounts.mws_marketplaceid',$marketplace_id)
        ->where('orders.purchase_local_date','>=',$date.' 00:00:00')
        ->where('orders.purchase_local_date','<=',$date.' 23:59:59')
        ->selectRaw('order_items.asin,sum(order_items.quantity_ordered) as sales,sum(order_items.item_price_amount-promotion_discount_amount)/sum((case when order_items.item_price_amount>0 then order_items.quantity_ordered else 0 end)) as price')
        ->groupBy(['asin'])->get()->keyBy('asin');
        /*
        ->map(function ($value) {
            return (array)$value;
        })->toArray();
        */
        $orderData = json_decode(json_encode($orderData),true);

        $domain ='www.'.array_get(getSiteUrl(),$marketplace_id);
        $reviewData = Starhistory::where('create_at',$date)->where('domain',$domain)->where('average_score','>',0)
        ->selectRaw('asin,total_star_number as review,average_score as rating')->get()->keyBy('asin');
        /*
        ->map(function ($value) {
            return (array)$value;
        })->toArray();
        */
        $reviewData = json_decode(json_encode($reviewData),true);

        if($this->option('with_stock')){
            $fbaData = DB::connection('amazon')->table('seller_skus')
            ->where('marketplaceid',$marketplace_id)->where('afn_total','>',0)
            ->selectRaw('asin,sum(afn_sellable) as fba_stock,sum(afn_reserved+afn_transfer) as fba_transfer')
            ->groupBy(['asin'])->get()->keyBy('asin');
            /*
            ->map(function ($value) {
                return (array)$value;
            })->toArray();
            */
            $fbaData = json_decode(json_encode($fbaData),true);
            $fbmData = Asin::where('site',$domain)->where('fbm_stock','>',0)->whereRaw("LENGTH(asin)=10")->selectRaw('asin,any_value(fbm_stock) as fbm_stock')
            ->groupBy(['asin'])->get()->keyBy('asin');
            /*
            ->map(function ($value) {
                return (array)$value;
            })->toArray();
            */
            $fbmData = json_decode(json_encode($fbmData),true);
        }else{
            $fbaData = $fbmData = [];
        }

        $updateData = array_merge_deep($orderData,$reviewData,$fbaData,$fbmData);
        foreach($updateData as $key=>$data){
            unset($data['asin']);
            Skusweekdetails::updateOrCreate(
                [
                    'asin'=>$key,
                    'marketplace_id'=>$marketplace_id,
                    'date'=>$date,
                ],
                $data
            );
        }


        
    }
}
