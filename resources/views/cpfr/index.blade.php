@extends('layouts.layout')
@section('label', 'CPFR协同补货')
@section('content')
<style>
	.daterangepicker .calendar-table table{
		display: grid;
	}
	.daterangepicker .calendar-table td, .daterangepicker .calendar-table th{
		background: #fff;
	}
	.daterangepicker td{
		float: left;
		line-height: 15px !important;
	}
	.button_box{
		text-align: right;
		padding: 20px 0;
	}
	.button_box > button{
		width: 105px;
		border-radius: 4px !important;
	}
	
	.content{
		padding: 30px 40px 40px 40px;
		overflow: hidden;
		border-radius: 4px !important;
		background-color: rgba(255, 255, 255, 1);
	}
	.filter_box{
		overflow: hidden;
		padding-bottom: 30px;
	}
	.filter_box select{
		border-radius: 4px !important;
		width: 150px;
		height: 36px;
		color: #666;
		border: 1px solid rgba(220, 223, 230, 1);
	}
	.filter_option{
		float: left;
		margin-right: 20px;
		padding-top: 10px;
	}
	.filter_option > label{
		display: block;
		color: rgba(48, 49, 51, 1);
		font-size: 14px;
		text-align: left;
		font-family: PingFangSC-Semibold;
	}
	.filter_option .btn{
		padding: 7px 0 7px 12px !important;
	}
	.btn.default:not(.btn-outline) {
	    background-color: #fff;
	    height: 34px;
	    border-right: none;
		width: 30px;
		line-height: 18px;
	}
	.input-group .form-control{
		border-left: none !important;
		border: 1px solid rgba(220, 223, 230, 1);
		background: #fff;
	}
	.keyword{
		outline: none;
		padding-left: 10px;
	}
	.search_box input{
		width: 250px;
		height: 36px;
		border-top-left-radius: 4px !important;
		border-bottom-left-radius: 4px !important;
		border: 1px solid rgba(220, 223, 230, 1);
	}
	.search{
		width: 90px;
		height: 36px;
		background-color: rgba(99, 197, 209, 1);
		border: 1px solid rgba(99, 197, 209, 1);
		margin-left: -5px;
		border-top-right-radius: 4px !important;
		border-bottom-right-radius: 4px !important;
		color: #fff;
		outline: none;
	}
	.search svg{
		display: inline-block;
		margin-bottom: -4px;
	}
	.clear{
		width: 90px;
		height: 36px;
		background-color: #909399;
		border: 1px solid #909399;
		border-radius: 4px !important;
		outline: none;
		color: #fff;
		margin-left: 20px;
	}
	table.table-bordered.dataTable th, table.table-bordered.dataTable td{
		text-align: center;
	}
	#planTable_filter{
		display: none;
	}
	.table-scrollable{
		margin: 0 0 10px 0 !important;
	}
	.batch_operation i{
		padding-left: 8px;
	}
	.btn.green-meadow:not(.btn-outline){
		/* height: 30px;
		line-height: 20px; */
	}
	.batch_list{
		border: 1px solid rgba(220, 223, 230, 1);
		width: 180px;
		margin-left: -40px !important;
		padding: 15px 0 !important;
		display: none;
	}
	.batch_list,.batch_list li{
		background: #fff;
		padding: 0;
		margin: 0;
		list-style: none;
	}
	.batch_list li{
		text-align: center;
	}
	.batch_list li button{
		color: #FFFFFF;
		border: none;
		width: 95px;
		margin: 5px 0;
	}
	.batch_list:after{
		position: absolute;
		top: 24px;
		left: 50px;
		right: auto;
		display: inline-block !important;
		border-right: 7px solid transparent;
		border-bottom: 7px solid #fff;
		border-left: 7px solid transparent;
		content: '';
		box-sizing: border-box;
	}
	.mask_box{
		display: none;
		position: fixed;
		top: 0;
		right: 0;
		bottom: 0;
		left: 0;
		background: rgb(0,0,0,.3);
		z-index: 999;
	}
	.mask-dialog{
		width: 600px;
		height: 740px;
		background: #fff;
		position: absolute;
		left: 50%;
		top: 50%;
		padding: 20px 60px;
		margin-top: -370px;
		margin-left: -300px;
	}
	.mask-form{
		overflow: hidden;
	}
	.mask-form > div:first-child{
		float: left;
	}
	.mask-form > div:last-child{
		float: right;
	}
	.mask-form > div{
		width: 45%;
	}
	.mask-form > div > input,.mask-form > div > select{
		width: 100%;
		height: 28px;
		margin-bottom: 10px;
		border: 1px solid rgba(220, 223, 230, 1)
	}
	.mask-form > div > label{
		display: block;
		text-align: left;
	}
	.form_btn{
		text-align: right;
		margin: 20px 0;
	}
	.form_btn button{
		width: 75px;
		height: 32px;
		outline: none;
		color: #fff;
		border-radius: 4px !important;
	}
	.form_btn button:first-child{
		background-color: #909399;
		border: 1px solid #909399;	
	}
	.form_btn button:last-child{
		margin-left: 10px;
		background-color: #3598dc;
		border: 1px solid #3598dc;
	}
	.mask-form > div > label > input{
		margin-right: 6px;
	}
	.cancel_mask{
		position: absolute;
		top: 20px;
		right: 20px;
		cursor: pointer;
		width: 30px;
		padding: 8px;
		height: 30px;
	}
	.default_btn:not(.btn-outline){
		height: 28px !important;
	}
	#maskDate{
		border-left:1px solid rgba(220, 223, 230, 1);
		border-right:1px solid rgba(220, 223, 230, 1);
		margin-bottom: 10px;
	}
	.nav_list{
		overflow: hidden;
		height: 45px;
		line-height: 45px;
		border-bottom: 2px solid #fff;
		padding: 0;
		margin: 0;
	}
	.nav_list li{
		float: left;
		line-height: 36px;
		padding: 5px 10px 0 10px;
		margin: 0 10px 0 0;
		list-style: none;
	}
	.nav_list li a{
		text-decoration: none;
		color: #666;
	}
	/* .nav_list li:first-child{
		margin-left: 0 !important;
	} */
	.nav_active{
		border-bottom: 2px solid #4B8DF8;
	}
	.nav_active a{
		color: #4B8DF8 !important;
	}
</style>
<link rel="stylesheet" type="text/css" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.0.5/daterangepicker.min.css" />
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.1/moment.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.0.5/daterangepicker.min.js"></script>
    <!-- <a href="/collaborativeReplenishment/index">Collaborative Replenishment</a> -->
	<ul class="nav_list">
		<li class="nav_active"><a href="/cpfr/index">调拨计划</a></li>
		<li><a href="/cpfr/purchase">采购计划</a></li>
		<li><a href="/cpfr/allocationProgress">调拨进度</a></li>
	</ul>
	<div class="button_box">
		<button id="sample_editable_1_2_new" class="btn sbold red"> Add New
			<i class="fa fa-plus"></i>
		</button>
		<button id="export" class="btn sbold blue"> Export
			<i class="fa fa-download"></i>
		</button>
	</div>
	<div class="content">
		<div class="filter_box">
			<div class="filter_option">
				<label for="marketplace_select">站点</label>
				<select id="marketplace_select" onchange="status_filter(this.value,2)">
					<option value ="">全部</option>
					<option value ="US">US</option>
					<option value ="CA">CA</option>
					<option value ="MX">MX</option>
					<option value ="UK">UK</option>
					<option value ="FR">FR</option>
					<option value ="DE">DE</option>
					<option value ="IT">IT</option>
					<option value ="ES">ES</option>
					<option value ="JP">JP</option>
				</select>
			</div>
			<div class="filter_option">
				<label for="bg_select">BG</label>
				<select id="bg_select" onchange="status_filter(this.value,0)">
					<option value ="">全部</option>
					<option value ="BG">BG</option>
					<option value ="BG1">BG1</option>
					<option value ="BG1">BG2</option>
					<option value ="BG2">BG3</option>
					<option value ="BG3">BG4</option>
				</select>
			</div>
			<div class="filter_option">
				<label for="bu_select">BU</label>
				<select id="bu_select" onchange="status_filter(this.value,1)">
					<option value ="">全部</option>
					<option value ="BU">BU</option>
					<option value ="BU1">BU1</option>
					<option value ="BU2">BU2</option>
					<option value ="BU3">BU3</option>
					<option value ="BU4">BU4</option>
					<option value ="BU5">BU5</option>
				</select>
			</div>
			
			<div class="filter_option">
				<label for="seller_select">Seller</label>
				<select id="seller_select" onchange="status_filter(this.value,7)">
					<option value ="">全部</option>
				</select>
			</div>
			<div class="filter_option">
				<label for="status_select">调拨状态</label>
				<select id="status_select"  onchange="status_filter(this.value,3)">
					<option value="">全部</option>
					<option value ="1">资料提供中</option>
					<option value ="2">换标中</option>
					<option value ="3">待出库</option>
					<option value ="4">已发货</option>
					<option value ="5">取消发货</option>
				</select>
			</div>
			<div class="filter_option">
				<label for="account_number">账号</label>
				<select id="account_number">
					<option value ="">全部</option>
				</select>
			</div>
			<div class="filter_option">
				<label for="">日期</label>
				<div class="input-group input-medium" id="createTimes">
					<span class="input-group-btn">
					    <button class="btn default date-range-toggle" type="button">
					        <i class="fa fa-calendar"></i>
					    </button>
					</span>
				    <input type="text" class="form-control createTimeInput" id="createTimeInput">  
				</div>
			</div>		
			<div class="filter_option search_box">
				<label for="">搜索</label>
				<input type="text" class="keyword">
				<button class="search">
					<svg t="1588043111114" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="3742" width="18" height="18"><path d="M400.696889 801.393778A400.668444 400.668444 0 1 1 400.696889 0a400.668444 400.668444 0 0 1 0 801.393778z m0-89.031111a311.637333 311.637333 0 1 0 0-623.331556 311.637333 311.637333 0 0 0 0 623.331556z" fill="#ffffff" p-id="3743"></path><path d="M667.904 601.998222l314.766222 314.823111-62.919111 62.976-314.823111-314.823111z" fill="#ffffff" p-id="3744"></path></svg>
					搜索
				</button>	
				<button class="clear">清空筛选</button>
			</div>
		</div>
		
		<div style="position: relative;">
			<div style="position: absolute;left: 130px; z-index: 999;top:0" class="col-md-2">
				<button type="button" class="btn btn-sm green-meadow batch_operation">批量操作<i class="fa fa-angle-down"></i></button>
				<ul class="batch_list">
					<li><button class="btn btn-sm red-sunglo noConfirmed">待计划确认</button></li>
					<li><button class="btn btn-sm yellow-crusta">BU经理审核</button></li>
					<li><button class="btn btn-sm purple-plum">BG总监审核</button></li>
					<li><button class="btn btn-sm green-meadow">已确认</button></li>
					<li><button class="btn btn-sm blue-madison">调拨取消</button></li>
				</ul>
			</div>
			<div class="col-md-5"  style="position: absolute;left: 520px; z-index: 999;top:0">
				<button type="button" class="btn btn-sm red-sunglo">待计划确认 : 22</button>
				<button type="button" class="btn btn-sm yellow-crusta">BU经理审核 : 22</button>
				<button type="button" class="btn btn-sm purple-plum">BG总监审核 : 2</button>
				<button type="button" class="btn btn-sm green-meadow">已确认 : 11</button>
				<button type="button" class="btn btn-sm blue-madison">调拨取消 : 2</button>
			</div>
			<table id="planTable" class="display table-striped table-bordered table-hover" style="width:100%">
				<thead>
					<tr style="text-align: center;">
						<th>BG</th>
						<th>BU</th>
						<th>Station</th>
						<th>planStatus</th>
						<th><input type="checkbox" id="selectAll" /></th>
						<th>提交日期</th>
						<th>销售员</th>
						<th>产品图片</th>
						<th>账号</th>
						<th>Seller SKU</th>
						<th>ASIN SKU</th>
						<th>调入工厂仓库</th>
						<th>需求数量</th>
						<th>期望到货时间</th>
						<th>是否贴标签</th>
						<th>其它需求</th>
						<th>可维持天数</th>
						<th>FBA在库</th>
						<th>FBA可维持天数</th>
						<th>调拨在途</th>
						<th>调拨可维持天数</th>
						<th>审核</th>
						<th>调整需求数量</th>
						<th>预计到货时间</th>
						<th>调出仓库库位</th>
						<th>调拨状态</th>
						<th>待办事项</th>
					</tr>
				</thead>
				
			</table>
		</div>
	</div>
	<div class="mask_box">
		<div class="mask-dialog">
			<svg t="1588919283810" class="icon cancel_mask cancel_btn" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="4128" width="15" height="15"><path d="M1001.952 22.144c21.44 21.44 22.048 55.488 1.44 76.096L98.272 1003.36c-20.608 20.576-54.592 20-76.096-1.504-21.536-21.44-22.048-55.488-1.504-76.096L925.824 20.672c20.608-20.64 54.624-20 76.128 1.472" p-id="4129" fill="#707070"></path><path d="M22.176 22.112C43.616 0.672 77.6 0.064 98.24 20.672L1003.392 925.76c20.576 20.608 20 54.592-1.504 76.064-21.44 21.568-55.488 22.08-76.128 1.536L20.672 98.272C0 77.6 0.672 43.584 22.176 22.112" p-id="4130" fill="#707070"></path></svg>
			<h4 style="text-align: center; line-height: 38px;">补货明细</h4>
			<label for="" style="display: block;">审核</label><select name="" id="" style="width:100%;height: 28px;margin-bottom: 20px;border: 1px solid rgba(220, 223, 230, 1);"></select>
			<form  method="post" onsubmit="return false" action="##" id="formtest">
				<div class="mask-form">
					<div>
						<label><input name="type" type="radio" value="" checked="checked" />调拨需求</label> 
						<label for="">SKU</label><select name="" id=""></select>
						<label for="">SellerSKU</label><select name="" id=""></select>
						<label for="">数量</label><input type="text">
						<label for="">RMS</label><select name="" id=""></select>
					</div>
					<div>
						<label><input name="type" type="radio" value="" />采购需求</label>
						<label for="">ASIN</label><select name="" id=""></select>
						<label for="">调入仓库</label><select name="" id=""></select>
						<label for="">到货时间</label>
						<div class="input-group date date-picker margin-bottom-5 bw9" id="maskDate">
							<input type="text" class="form-control form-filter input-sm maskDate" style="height: 28px;" readonly name="date_from" placeholder="From" value="">
							<span class="input-group-btn">
								<button class="btn btn-sm default default_btn" type="button">
									<i class="fa fa-calendar"></i>
								</button>
							</span>
						</div>
						<label for="">RMS</label><select name="" id=""></select>
					</div>
				</div>
				<div style="border-bottom: 1px dashed rgba(220, 223, 230, 1);padding-bottom: 10px;">
					<label for="" style="display: block;">备注</label><input type="text" style="width: 100%;margin-bottom: 10px;height: 28px;border: 1px solid rgba(220, 223, 230, 1)">
				</div>
				<div class="mask-form" style="padding-top: 10px;">
					<div>
						<label for="">调整需求数量</label><select name="" id=""></select>
						<label for="">调出仓库库位</label><select name="" id=""></select>
					</div>
					<div>
						<label for="">预计到货时间</label><select name="" id=""></select>
						
					</div>
				</div>
			</form>
			<div class="form_btn">
				<button class="cancel_btn">取消</button>
				<input type="hidden" class="formId">
				<button class="confirm">确认</button>
			</div>
			
		</div>
	</div>
	
	<script>
		//筛选
		function status_filter(value,column) {
		    if (value == '') {
		        tableObj.column(column).search('').draw();
		    }
		    else tableObj.column(column).search(value).draw();
		}
		$(document).ready(function () {
			/* $("#planTable tr td:first-child").click(function(event){
			    event.cancelBubble=true;
			    event.stopPropagation();
			}); */
			//提交
			$('.confirm').on('click',function(){
				console.log($('.formId').val())
			})
			//全选
			$("#selectAll").on('change',function(e) {  
			    $("input[name='checkedInput']").prop("checked", this.checked);
				//let checkedBox = $("input[name='checkedInput']:checked");
			});  
			//单条选中
			$("body").on('change','.checkbox-item',function(e){
				var $subs = $("input[name='checkedInput']");
			    $("#selectAll").prop("checked" , $subs.length == $subs.filter(":checked").length ? true :false); 
				e.cancelBubble=true;
			});
			//时间选择器
			$('.date-picker').datepicker({
				format: 'yyyy-mm-dd',
			    autoclose: true,
				datesDisabled : new Date(),
				startDate: '0',
			});
			$('.cancel_btn').on('click',function(){
				$('.mask_box').hide();
			})
			$('.submit').on('click',function(){
				$.ajax({
				    type: "POST",//方法类型
				    dataType: "text",//预期服务器返回的数据类型 如果是对象返回的是json 如果是字符串这里一定要定义text 之前我就是定义json 结果字符串的返回一直到额error中去
					url: "",//url
					data: $('#formtest').serialize(),//这个是form表单中的id   jQuery的serialize()方法通过序列化表单值
					success: function (result) {
						console.log(result);
					},
					error : function(err) {
						console.log(err)
					}
				});
			})
			$('.batch_operation').click(function(e){
				$('.batch_list').slideToggle();
				$(document).one('click',function(){
					$('.batch_list').hide();
				})
				e.stopPropagation();
			})
			//待计划确认
			$('.noConfirmed').on('click',function(){
				let chk_value = '';
				$("input[name='checkedInput']:checked").each(function () {
					console.log($(this).val())
					if(chk_value != ''){
						chk_value = chk_value + ',' + $(this).val()	
					}else{
						chk_value = chk_value + $(this).val()
					}				 		 			
				});
				chk_value == ""? chk_value = -1 : chk_value;
				tableObj.ajax.reload();
				console.log(chk_value)
			})
			
			//禁止警告弹窗弹出
			$.fn.dataTable.ext.errMode = 'none';
			
			tableObj = $('#planTable').DataTable({
				lengthMenu: [
				    20, 50, 100, 'All'
				],
				dispalyLength: 2, // default record count per page
				paging: true,  // 是否显示分页
				info: false,// 是否表格左下角显示的文字
				ordering: false,
				serverSide: false,//是否所有的请求都请求服务器	
				scrollX: "100%",
				scrollCollapse: false,
				ajax: {
					url: "",
					type: "post",
					data : function(){
						reqList = {
							/* "sap_seller_id" : sap_seller_id,
							"created_at_s": cusstr($('.createTimeInput').val() , ' - ' , 1),
							"created_at_e": cusstr1($('.createTimeInput').val() , ' - ' , 1),
							"from_time": cusstr($('.estTimeInput').val() , ' - ' , 1),
							"to_time": cusstr1($('.estTimeInput').val() , ' - ' , 1),
							"condition": $('.keyword').val(), */
						};
						return reqList;
					},
					dataSrc:function(res){
						console.log(res)
						return res;
					},
				},			
				data: [
					{
						date1: '111',
						date2: '111',
						date3: '111',
						date4: '111',
						date5: '111',
						date6: '111',
						date7: '111',
						date8: '111',
						date9: '111',
						date10: '111',
						date11: '111',
						date12: '111',
						date13: '111',
						date14: '111',
						date15: '111',
						date16: '111',
						date17: '111',
						date18: '111',
						date19: '111',
						date20: '111',
						date21: '111',
						date22: '111',
						id:1,
					},
					{
						date1: '111',
						date2: '111',
						date3: '111',
						date4: '111',
						date5: '111',
						date6: '111',
						date7: '111',
						date8: '111',
						date9: '111',
						date10: '111',
						date11: '111',
						date12: '111',
						date13: '111',
						date14: '111',
						date15: '111',
						date16: '111',
						date17: '111',
						date18: '111',
						date19: '111',
						date20: '111',
						date21: '111',
						date22: '111',
						id:2,
					},
					{
						date1: '111',
						date2: '111',
						date3: '111',
						date4: '111',
						date5: '111',
						date6: '111',
						date7: '111',
						date8: '111',
						date9: '111',
						date10: '111',
						date11: '111',
						date12: '111',
						date13: '111',
						date14: '111',
						date15: '111',
						date16: '111',
						date17: '111',
						date18: '111',
						date19: '111',
						date20: '111',
						date21: '111',
						date22: '111',
						id:3,
					},
					{
						date1: '111',
						date2: '111',
						date3: '111',
						date4: '111',
						date5: '111',
						date6: '111',
						date7: '111',
						date8: '111',
						date9: '111',
						date10: '111',
						date11: '111',
						date12: '111',
						date13: '111',
						date14: '111',
						date15: '111',
						date16: '111',
						date17: '111',
						date18: '111',
						date19: '111',
						date20: '111',
						date21: '111',
						date22: '111',
						id:4,
					},
					{
						date1: '111',
						date2: '111',
						date3: '111',
						date4: '111',
						date5: '111',
						date6: '111',
						date7: '111',
						date8: '111',
						date9: '111',
						date10: '111',
						date11: '111',
						date12: '111',
						date13: '111',
						date14: '111',
						date15: '111',
						date16: '111',
						date17: '111',
						date18: '111',
						date19: '111',
						date20: '111',
						date21: '111',
						date22: '111',
						id:5,
					}
				],
				columns: [
					{
						data: "bg" ,
						visible: false,
					},
					{
						data: "bu" ,
						visible: false,
					},
					{
						data: 'station',
						visible: false,
					},
					{
						data: 'plan_status',
						visible: false,
					},
					{
						data: "id",
						orderable: false,
						bSortable: false,
						render: function(data, type, row, meta) {
							var content = '<input type="checkbox" name="checkedInput"  class="checkbox-item" value="' + data + '" />';
							return content;
						},
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							
						}
					},
					{
						data: "date1",
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: 'date2',
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: 'date3',
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: 'date4',
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{ 
						data: 'date5',
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: 'date6',
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: 'date7',
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: 'date8',
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: "date9",
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: "date10",
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: "date11",
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: "date12",
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: "date13",
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: "date14",
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: "date15",
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: "date16",
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: "date17",
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: "date18",
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: "date19",
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: "date20",
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: "date21",
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
					{
						data: "date22",
						createdCell: function (cell, cellData, rowData, rowIndex, colIndex) {
							$(cell).on( 'click', function () {
								$('.mask_box').show();
								$('.formId').val(rowData.id); 
							});
						}
					},
				], 
				
			});
			
			$("#createTimes").daterangepicker({
			    opens: "left", //打开的方向，可选值有'left'/'right'/'center'
			    format: "YYYY-MM-DD",
				autoUpdateInput: false,
			    separator: " to ",
			    startDate: moment(),
			    endDate: moment(),
				opens: 'center',
			    ranges: {
			        "今天": [moment(), moment()],
			        "昨天": [moment().subtract("days", 1), moment().subtract("days", 1)],
			        "7天前": [moment().subtract("days", 6), moment()],
			        "30天前": [moment().subtract("days", 29), moment()],
			        "这个月": [moment().startOf("month"), moment().endOf("month")],
			        "上个月": [moment().subtract("month", 1).startOf("month"), moment().subtract("month", 1).endOf("month")]
			    },
			    locale: {
			        applyLabel: '确定',
			        cancelLabel: '取消',
			        fromLabel: '起始时间',
			        toLabel: '结束时间',
			        customRangeLabel: '自定义',
			        daysOfWeek: ['日', '一', '二', '三', '四', '五', '六'],
			        monthNames: ['一月', '二月', '三月', '四月', '五月', '六月','七月', '八月', '九月', '十月', '十一月', '十二月'],
			        firstDay: 1,
			
			    },
				onChangeDateTime:function(dp,$input){
				    console.log(1)
				}
			    /* minDate: "01/01/2012",
			    maxDate: "12/31/2018" */
			}, function (t, e) {
				$("#seller_select").empty();
				$("#seller_select").append("<option value=''>全部</option>");
			    $("#createTimes input").val(t.format("YYYY-MM-DD") + " - " + e.format("YYYY-MM-DD"));	
				let reqList = {
					"created_at_s": cusstr($('#createTimes input').val() , ' - ' , 1),
					"created_at_e": cusstr1($('#createTimes input').val() , ' - ' , 1),
				};
				let val = ''
				//tableObj.ajax.reload();
				//handleClear();
				//status_filter(val,0);
				//status_filter(val,1);
				//status_filter(val,2);
				//status_filter(val,3);
				//status_filter(val,7);
				//status_filter(val,8);
			})
			//截取字符前面的
			function cusstr(str, findStr, num){
				if(str.length > 0){
					let idx = str.indexOf(findStr);
					let count = 1;
					while(idx >= 0 && count < num){
					    idx = str.indexOf(findStr, idx+1);
					    count++;
					}    
					if(idx < 0){
					    return '';
					}
					return str.substring(0, idx);
				}else{
					return ''
				}
			}
			//截取字符前面的
			function cusstr1(str, findStr, num){
				if(str.length > 0){
					let idx = str.indexOf(findStr);
					let count = 1;
					while(idx >= 0 && count < num){
						idx = str.indexOf(findStr, idx+1);
						count++;
					}    
					if(idx < 0){
						return '';
					}
					return str.substring(idx+3);
				}else{
					return ''
				}
			}
		})
		
	</script>
@endsection