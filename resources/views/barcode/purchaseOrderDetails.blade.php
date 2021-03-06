<!doctype html>
<html>
<head>
    <title></title>
    <link href="/assets/global/css/components.css" rel="stylesheet" id="style_components" type="text/css"/>
    <link href="/assets/global/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
    <link href="/assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet"
          type="text/css"/>
    <link href="/assets/global/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css"/>
    <link href="/assets/global/css/components.css" rel="stylesheet" id="style_components" type="text/css"/>

    <script src="/assets/global/plugins/jquery.min.js" type="text/javascript"></script>
    <script src="/assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="/assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
    <script src="/assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js"
            type="text/javascript"></script>
    <style>
        .search-btn {
            background-color: #63C5D1;
            color: #ffffff;
            font-size: 14px;
            text-align: center;
            width: 70px;
            height: 30px;
            border-radius: 5px 5px 5px 5px !important;
        }

        .common-btn {
            background-color: #63C5D1;
            color: #ffffff;
            font-size: 14px;
            text-align: center;
            width: 70px;
            height: 30px;
            border-radius: 5px !important;
        }

    </style>
</head>
<body>
<div class="row">
    <div class="col-md-12">
        <!-- BEGIN EXAMPLE TABLE PORTLET-->
        <div class="portlet light bordered">
            <div style="height: 15px;"></div>

            <div class="portlet-title">
                <label>供应商代码：{{$vendorCode}}</label>
                <div style="height: 5px;"></div>
                <label>采购订单号：{{$purchaseOrder}}</label>
                <form id="search-form">
                    {{--                    {{ csrf_field() }}--}}
                    <input type="hidden" name='vendorCode' id='vendorCode' value="{{$vendorCode}}"/>
                    <input type="hidden" name='purchaseOrder' id='purchaseOrder' value="{{$purchaseOrder}}"/>
                                        <input type="hidden" name='skuHidden' id="skuHidden" value=""/>

                    <div class="table-toolbar" id="thetabletoolbar">
                        <div class="input-group">
                            <input type="text" name="sku" id="sku"
                                   style="width: 300px; height: 29px; border: 1px solid #dddddd;"
                                   placeholder="输入SKU"/>
                            <button id="search" type="button" class="search-btn">搜索
                            </button>
                        </div>
                    </div>
                </form>
                <div>
                    <button type="button" class="common-btn" id="export-btn" style="width: 80px"><span><i
                                    class="fa fa-sign-out"></i></span> 导出
                    </button>
                </div>
            </div>
            <div style="height: 20px;"></div>
            <div class="portlet-body">
                <div class="table-container">

                    <div style="overflow:auto;width: 100%;">
                        <table class="table table-striped table-bordered table-hover table-checkable" id="thetable">
                            <thead>
                            <tr role="row" class="heading">
                                <th>SKU</th>
                                <th>条码</th>
                                <th>条码当前状态</th>
                                <th>条码历史状态</th>
                                <th>条码最后更新时间</th>
                                <th>条码生成人</th>
                                <th>条码生成时间</th>
                                <th>条码打印人</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade bs-modal-lg" id="ajax" role="basic" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-body">
                    <img src="../assets/global/img/loading-spinner-grey.gif" alt="" class="loading">
                    <span>Loading... </span>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    let $theTable = $(thetable)
    var initTable = function () {
        $theTable.dataTable({
            searching: false,//关闭搜索
            serverSide: true,//启用服务端分页（这是使用Ajax服务端的必须配置）
            "lengthMenu": [
                [10, 50, 100, -1],
                [10, 50, 100, 'All'] // change per page values here
            ],
            "pageLength": 50, // default record count per page
            pagingType: 'bootstrap_extended',
            //processing: true,
            scrollX: false,
            ordering: true,
            aoColumnDefs: [{"bSortable": false, "aTargets": [3, 5, 7]}],
            order: [],
            columns: [
                {data: 'sku', name: 'sku'},
                {data: 'barcode_text', name: 'barcode_text'},
                {data: 'current_status', name: 'current_status'},
                {data: 'status_history', name: 'status_history'},
                {data: 'status_updated_at', name: 'status_updated_at'},
                {data: 'generated_by', name: 'generated_by'},
                {data: 'generated_at', name: 'generated_at'},
                {data: 'printed_by', name: 'printed_by'},
            ],
            ajax: {
                type: 'POST',
                url: "{{ url('barcode/getPurchaseOrderDetails')}}",
                data: {
                    search: decodeURIComponent($("#search-form").serialize().replace(/\+/g, " "), true),
                    '_token': '{{csrf_token()}}'
                },
            }
        });
    }

    initTable();
    let dtApi = $theTable.api();
    //点击提交按钮重新绘制表格，并将输入框中的值赋予检索框
    $('#search').click(function () {
            $sku = $('#sku').val();
        $('#skuHidden').val($sku);

        dtApi.settings()[0].ajax.data = {search: decodeURIComponent($("#search-form").serialize().replace(/\+/g, " "), true)};
        dtApi.ajax.reload();
        return false;
    });
    $(function () {
        $("#ajax").on("hidden.bs.modal", function () {
            $(this).find('.modal-content').html('<div class="modal-body"><img src="../assets/global/img/loading-spinner-grey.gif" alt="" class="loading"><span>Loading... </span></div>');
        });
    });
        //下载数据
    $("#export-btn").click(function () {
        location.href = '/barcodePoDetailsExport?vendorCode=' + $("#vendorCode").val() + '&purchaseOrder=' + $("#purchaseOrder").val() + '&sku=' + $("#skuHidden").val();
        return false;
    });


</script>
</body>
</html>

