<?php
return array(
	"GOODS_COLUMN_SHOW" => "gname_s=0,gtname_s=0,pic_s=1,bname_s=0,price_s=0,uname_s=0,ctime_s=0", //配置默认商品数据列数据显示状态 0.显示 1.不显示
	"GOODS_COLUMN_NAME" => "gname=商品名称,gtname=商品类型名称,pic=图片,bname=品牌,price=进价(元),uname=单位,ctime=录入时间", //配置默认商品数据列显示标题
	"GOODS_COLUMN_NEED" => "gname=0,gtname=1,pic=1,bname=1,price=0,uname=0,ctime=1", //配置商品数据列必需列 0.必需 1.不必需

	//入库头部
    "JOIN_STOCK_COLUMN_SHOW_T" =>"jsid_s=0,jsname_s=0,sname_s=0,company_s=1,company_address_s=1,company_phone_s=1,ctime_s=1,cfee_s=1,ufee_s=1,afee_s=1,remark_s=1,total_s=0,saddress_s=1,stel=1,audit_s=1,settlement_s=1",
	"JOIN_STOCK_COLUMN_NAME_T" =>"jsid=入库单据号,jsname=入库名称,sname=供应商,company=公司,company_address=公司地址,company_phone=公司电话,ctime=日期,cfee=运费(元),ufee=卸车费(元),afee=代理费(元),remark=备注,total=单据金额(元),saddress=供应商地址,stel=供应商电话,audit=审核人,settlement=是否结算",
	"JOIN_STOCK_COLUMN_NEED_T" =>"jsid=0,jsname=1,sname=0,company=1,company_address=1,company_phone=1,ctime=1,cfee=1,ufee=1,afee=1,remark=1,total=0,saddress=1,stel=1,audit=1,settlement=1",
	//入库内容
	"JOIN_STOCK_COLUMN_SHOW" =>"gname_s=0,bname_s=0,uname_s=0,num_s=0,price_s=0,totalb_s=0,remarkb_s=0",
	"JOIN_STOCK_COLUMN_NAME" =>"gname=商品名称,bname=品牌,uname=默认单位,num=数量,price=单位进价,totalb=总金额,remarkb=备注",
	"JOIN_STOCK_COLUMN_NEED" =>"gname=0,bname=1,uname=0,num=0,price=0,totalb=0,remarkb=0",
	//出库头部
    "OUT_STOCK_COLUMN_SHOW_T" =>"osid_s=0,osname_s=0,cname_s=0,cphone_s=0,caddress_s=0,company_s=1,company_address_s=1,company_phone_s=1,ctime_s=0,remark_s=0,total_s=0,audit_s=0,check_s=0,delivery_s=1,checkp_s=1,take_over_s=1",
	"OUT_STOCK_COLUMN_NAME_T" =>"osid=单据号,osname=单据名称,cname=客户,cphone=客户电话,caddress=客户地址,company=公司,company_address=公司地址,company_phone=公司电话,ctime=日期,remark=备注,total=单据金额(元),audit=审核人,check=验收人,delivery=送货人,checkp=复核人,take_over=收货人（接管人）",
	"OUT_STOCK_COLUMN_NEED_T" =>"osid=0,osname=1,cname=0,cphone=1,caddress=1,company=1,company_address=1,company_phone=1,ctime=1,remark=1,total=0,audit=1,check=1,delivery=0,checkp=0,take_over=0",
	//出库内容
	"OUT_STOCK_COLUMN_SHOW" =>"gname_s=0,alias_s=0,bname_s=0,uname_s=0,num_s=0,p_remark_s=1,num1_s=1,than_s=0,price_s=0,tax_price_s=0,price1_s=1,j_price=1,cd_num=0,stock_s=0,remarkb_s=0,totalb_s=0,rweight_s=1,machining_s=1,check_num_s=1,totalb1_s=1",
	"OUT_STOCK_COLUMN_NAME" =>"gname=商品名称,alias=别名,bname=品牌,uname=默认单位,num=数量,p_remark=单位备注,num1=备用数量,than=单价比,price=售价(元),tax_price=税后单价,price1=备用单价,j_price=进价,cd_num=货损数量,stock=库存剩余,remarkb=备注,totalb=总金额,rweight=毛重,machining=是否加工,check_num=验收数量,totalb1=备用总金额",
	"OUT_STOCK_COLUMN_NEED" =>"gname=0,alias=1,bname=1,uname=0,num=0,p_remark=0,num1=1,than=1,price=0,tax_price=1,price1=1,j_price=1,cd_num=1,stock=1,remarkb=1,totalb=0,rweight=1,machining=1,check_num=0,totalb1=1",

	//销售总汇表
    "REPORTS_COLUMN_SHOW" =>"gtname_s=0,gname_s=0,alias_s=0,bname_s=0,uname_s=0,num_s=0,cd_num_s=0,snum=0",
	"REPORTS_COLUMN_NAME" =>"gtname=商品类别,gname=商品名称,alias=别名,bname=品牌,uname=单位,num=数量,cd_num=货损数量,snum=数量总计",
	"REPORTS_COLUMN_NEED" =>"gtname=1,gname=1,alias=1,bname=1,uname=1,num=1,cd_num=1,snum=1",//配置销售汇总数据列必需列
    //日销售明细
    "DAYSALE_COLUMN_SHOW" =>"gname_s=0,alias_s=0,gtname_s=0,bname_s=0,uname_s=0,num_s=0,rweight_s=0,than_s=0,price_s=0,cd_num_s=0,total_s=0,cname_s=0,remark_s=0,check_num_s=0",
    "DAYSALE_COLUMN_NAME" =>"gname=商品名称,alias=别名,gtname=商品类型,bname=品牌,uname=单位,num=数量,rweight=毛重,than=单价比,price=价格,cd_num=货损数,total=总金额,cname=客户名称,remark=备注,check_num=验收数量",
    "DAYSALE_COLUMN_NEED" =>"gname=1,alias=1,gtname=1,bname=1,uname=1,num=1,rweight=1,than=1,price=1,cd_num=1,total=1,cname=1,remark=1,check_num=1", 
    //财务分析
    "FINANCE_COLUMN_SHOW" =>"gname_s=0,uname_s=0,num_s=0,cd_num_s=0,cd_cost_s=0,profit_s=0,cost_s=0,rate_s=0",
    "FINANCE_COLUMN_NAME" =>"gname=商品名称,uname=商品单位,num=商品数量,cd_num=货损数量,cd_cost=货损成本,profit=毛利,cost=销售成本,rate=销售毛利率",
    "FINANCE_COLUMN_NEED" =>"gname=0,uname=0,num=0,cd_num=1,cd_cost=1,profit=0,cost=0,rate=0",

    //参数设置默认值
    "SYSTEM_PARAM" =>"param_num=2,param_price=2,param_sum=2,param_is_round=1,param_is_convert_unit=0",

);