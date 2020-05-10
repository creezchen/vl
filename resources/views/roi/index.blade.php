@extends('layouts.layout')
@section('crumb')
    @include('layouts.crumb', ['crumbs'=>['ROI Analysis']])
@endsection
@section('content')


    <style type="text/css">
        .dataTables_extended_wrapper .table.dataTable {
            margin: 0px !important;
        }

        table.dataTable thead th, table.dataTable thead td {
            padding: 10px 2px !important;}
        table.dataTable tbody th, table.dataTable tbody td {
            padding: 10px 2px;
        }
        th{
            text-align: center;
        }
        th,td,td>span {
            font-size:12px !important;
            font-family:Arial, Helvetica, sans-serif;
        }

        #thetabletoolbar{
            margin-top: 10px;
            margin-bottom:0px !important;
        }

        .search-btn{
            background-color: #63C5D1;
            color: #ffffff;
            font-size: 14px;
            text-align: center;
            width: 70px;
            height: 30px;
            border-radius: 0px 5px 5px 0px !important;
        }

        .common-btn{
            background-color: #63C5D1;
            color: #ffffff;
            font-size: 14px;
            text-align: center;
            width: 70px;
            height: 30px;
            border-radius: 5px !important;
        }
    </style>
    <div class="row">
        <div class="col-md-12">
            <div style="height: 20px;"></div>
            <div style="float: right;">
                <button type="button" class="common-btn" id="export-btn" style="width: 80px"><span><i class="fa fa-sign-out"></i></span> 导出</button>
            </div>
            <div style="float: right;">
                <a href="{{ url('roi/create') }}"><button type="button" class="common-btn" style="margin-right: 10px;">添加</button></a>
            </div>
            <div style="clear:both"></div>
            <div style="height: 20px;"></div>
        </div>

        <div class="col-md-12">
            <!-- BEGIN EXAMPLE TABLE PORTLET-->
            <div class="portlet light bordered">
                <div style="height: 15px;"></div>

                <div class="portlet-title">
                    {{--新添加的状态统计数据--}}
                    <form id="search-form">
                        <div class="table-toolbar" id="thetabletoolbar">
                            <div style="float:left; width:343px;">
                                <div>创建日期</div>
                                <div class="pull-left">
                                    <div class="input-group date date-picker pull-left" data-date-format="yyyy-mm-dd">
                                <span style="width:20px; height:26px" class="input-group-btn">
                                    <button class="btn btn-sm default time-btn" type="button">
                                        <i class="fa fa-calendar"></i>
                                    </button>
                                </span>
                                        <input type="text" style="width:125px" class="form-control form-filter input-sm" readonly placeholder="开始日期" value="{{$submit_date_from}}" id="date_from" name="submit_date_from" />
                                    </div>
                                    <div style="float:left; width:12px; text-align:center">--</div>
                                    <div class="input-group date date-picker pull-left" data-date-format="yyyy-mm-dd">
                                <span style="width:20px; height:26px" class="input-group-btn">
                                    <button class="btn btn-sm default time-btn" type="button">
                                        <i class="fa fa-calendar"></i>
                                    </button>
                                </span>
                                        <input type="text" style="width:125px" class="form-control form-filter input-sm" readonly placeholder="结束日期" value="{{$submit_date_to}}" id="date_to" name="submit_date_to" />
                                    </div>

                                </div>
                            </div>
                            <div style="width:220px; float:left">
                                <div>销售部门</div>
                                <select name="bgbu" id="bgbu" style="width:205px; height:30px">
                                    <option value="">请选择销售部门</option>
                                    {{--<option value="">BG && BU</option>--}}
                                    <?php
                                    $bg='';
                                    foreach($teams as $team){
                                        $selected = '';
//                                        if($bgbu==($team->bg.'_')) $selected = 'selected';

                                        if($bg!=$team->bg) echo '<option value="'.$team->bg.'_" '.$selected.'>'.$team->bg.'</option>';
                                        $bg=$team->bg;
                                        $selected = '';
//                                        if($bgbu==($team->bg.'_'.$team->bu)) $selected = 'selected';
                                        if($team->bg && $team->bu) echo '<option value="'.$team->bg.'_'.$team->bu.'" '.$selected.'>'.$team->bg.' - '.$team->bu.'</option>';
                                    } ?>
                                </select>
                            </div>
                            <div style="width:220px; float:left">
                                <div>销售人员</div>
                                <select name="user_id" id="user_id" style="width:205px; height:30px">
                                    <option value="">请选择销售人员</option>
                                    @foreach ($users as $user_id=>$user_name)
                                        <option value="{{$user_id}}">{{$user_name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="width:220px; float:left">
                                <div>站点</div>
                                <select name="site" id="site" style="width:205px; height:30px">
                                    <option value="">请选择销售站点</option>
                                    @foreach ($sites as $site)
                                        <option value="{{$site}}">{{$site}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="width:220px; float:left">
                                <div>归档状态</div>
                                <select name="archived_status" id="archived_status" style="width:205px; height:30px">
                                    <option value="">所有</option>
                                    <option value="1">已归档</option>
                                    <option value="0">未归档</option>
                                </select>
                            </div>
                            <div style="clear:both"></div>
                            <div style="height: 15px;"></div>
                            <div class="input-group">
                                <input type="text" name="keyword" id="keyword" style="width: 360px; height: 29px" placeholder="输入产品名称" />
                                <button id="search" type="button" class="search-btn input-group-addon"><span><i class="fa fa-search"></i></span> 搜索</button>
                            </div>

                        </div>
                    </form>

                </div>
                <div style="height: 20px;"></div>
                <div class="portlet-body">
                    <div class="table-container">

                        <div style="overflow:auto;width: 100%;">
                            <table class="table table-striped table-bordered table-hover table-checkable" id="thetable">
                                <thead>
                                <tr role="row" class="heading">
                                    <th onclick="this===arguments[0].target && this.firstElementChild.click()">
                                        <input type="checkbox" onchange="this.checked?dtApi.rows().select():dtApi.rows().deselect()" id="selectAll"/>
                                    </th>
                                    <th>项目ID</th>
                                    <th>产品名称/SKU</th>
                                    <th>站点</th>
                                    <th>预计上线日期</th>
                                    <th>预计年销量</th>
                                    <th>预计年销售额</th>
                                    <th>资金周转次数</th>
                                    <th>项目利润率</th>
                                    <th><div>投资回报率</div><div>ROI(%)</div></th>
                                    <th><div>投资回报额</div><div>万元</div></th>
                                    <th>创建人</th>
                                    <th>创建日期</th>
                                    <th>最新修改人</th>
                                    <th>最新修改日期</th>
                                    <th>归档状态</th>
                                    <th>操作</th>

                                </tr>
                                </thead>
                                <tbody> </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- END EXAMPLE TABLE PORTLET-->
        {{--</div>--}}
    </div>


    <div class="modal fade bs-modal-lg" id="ajax" role="basic" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" >
                <div class="modal-body" >
                    <img src="../assets/global/img/loading-spinner-grey.gif" alt="" class="loading">
                    <span>Loading... </span>
                </div>
            </div>
        </div>
    </div>
    <script>
        $("#thetabletoolbar [id^='date']").each(function () {

            let defaults = {
                autoclose: true
            }

            let options = eval(`({${$(this).data('options')}})`)

            $(this).datepicker(Object.assign(defaults, options))
        });

        let $theTable = $(thetable)

        var initTable = function () {
            $theTable.dataTable({
                searching: false,//关闭搜索
                serverSide: true,//启用服务端分页（这是使用Ajax服务端的必须配置）
                "lengthMenu": [
                    [10, 50, 100, -1],
                    [10, 50, 100, 'All'] // change per page values here
                ],
                "pageLength": 10, // default record count per page
                pagingType: 'bootstrap_extended',
                processing: true,
                ordering:  true,
                aoColumnDefs: [ { "bSortable": false, "aTargets": [0,2,3,4,5,6,7,8,9,10,11,13,15,16] }],
                order: [],
                select: {
                    style: 'os',
                    info: true, // info N rows selected
                    // blurable: true, // unselect on blur
                    selector: 'td:first-child', // 指定第一列可以点击选中
                },
                columns: [
                    {
                        width: "1px",
                        defaultContent: '',
                        className: 'select-checkbox', // 该类根据 tr:selected 改变自己的背景
                    },
                    {data: 'roi_id', name: 'roi_id'},
                    {data: 'product_name_sku', name: 'product_name_sku'},
                    {data: 'site', name: 'site'},
                    {data: 'estimated_launch_time', name: 'estimated_launch_time'},
                    {data: 'total_sales_volume', name: 'total_sales_volume'},
                    {data: 'total_sales_amount', name: 'total_sales_amount'},
                    {data:'capital_turnover',name:'capital_turnover'},
                    {data:'project_profitability',name:'project_profitability'},
                    {data: 'roi', name: 'roi'},
                    {data:'return_amount',name:'return_amount'},
                    {data:'creator',name:'creator'},
                    {data:'created_at',name:'created_at'},
                    {data:'updated_by',name:'updated_by'},
                    {data:'updated_at',name:'updated_at'},
                    {data:'archived_status',name:'archived_status'},
                    {data:'action',name:'action'},
                ],
                ajax: {
                    type: 'POST',
                    url: "{{ url('roi/get')}}",
                    data:  {search: decodeURIComponent($("#search-form").serialize().replace(/\+/g," "),true)},
                }
            });
        }

        initTable();
        let dtApi = $theTable.api();

        var grid = new Datatable();
        //设置负责人操作、修改状态值
        $('.table-action-submit').click(function(){
            var type = $(this).attr('data-type');
            customstatus = '';
            if(type==1){
                var customstatus = $("#processor", grid.getTableWrapper());
            }else if(type==2){
                var customstatus = $("#customstatus", grid.getTableWrapper());
            }

            let selectedRows = dtApi.rows({selected: true})
            let ctgRows = selectedRows.data().toArray().map(obj => [obj.id]);//选中的行的id

            if ((customstatus.val() != "") && ctgRows.length > 0) {
                $.ajax({
                    type: 'post',
                    url: '/rsgrequests/updateAction',
                    data: {type:type,data:customstatus.val(),id:ctgRows},
                    dataType: 'json',
                    success: function(res) {
                        if(res){
                            //动态改变已修改的值，不用重新加载数据
                            dtApi.ajax.reload();
                            toastr.success('Saved !');
                        }else{
                            //编辑失败
                            toastr.error('Failed');
                        }
                    }
                });
            } else if (customstatus.val() == "") {
                toastr.error('Please select an processor !')
            } else if (!ctgRows.length) {
                toastr.error('Please select some rows first !')
            }
        });

        //点击提交按钮重新绘制表格，并将输入框中的值赋予检索框
        $('#search').click(function () {
            dtApi.settings()[0].ajax.data = {search: decodeURIComponent($("#search-form").serialize().replace(/\+/g," "),true)};
            dtApi.ajax.reload();
            return false;
        });

        //起止时间范围和下拉框改变值的时候，自动更新数据
        $('#date_from').change(function(){
            $("#search").trigger("click");
        });
        $('#date_to').change(function(){
            $("#search").trigger("click");
        });
        $('#bgbu').change(function(){
            $("#search").trigger("click");
        });
        $('#user_id').change(function(){
            $("#search").trigger("click");
        });
        $('#site').change(function(){
            $("#search").trigger("click");
        });
        $('#archived_status').change(function(){
            $("#search").trigger("click");
        });

        $(function() {
            $("#ajax").on("hidden.bs.modal",function(){
                $(this).find('.modal-content').html('<div class="modal-body"><img src="../assets/global/img/loading-spinner-grey.gif" alt="" class="loading"><span>Loading... </span></div>');
            });

            $('.date-picker').datepicker({
                rtl: App.isRTL(),
                format: 'yyyy-mm-dd',
                orientation: 'bottom',
                autoclose: true,
            });

        });


        //下载数据
        $("#export-btn").click(function(){
            location.href='/roi_export?date_from='+$("#date_from").val()+'&date_to='+$("#date_to").val();
            return false;
        });

    </script>

@endsection