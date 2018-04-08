//根据编号获取省\市\县区名称 
function getAddressName(pid,cid,did){
	if(did) 
		return _dataArea[pid].son[cid].son[did].name;  
	if(cid)
		return _dataArea[pid].son[cid].name; 
	if(pid)
		return _dataArea[pid].name;
}