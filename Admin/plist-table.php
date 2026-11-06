<?php
$mod = 'admin';
include('../Common/Core_brain.php');

if(isset($_GET['value']) && !empty($_GET['value'])) {
	if ($_GET['column'] == 1) {
		$sql=" 1";
	}elseif ($_GET['value'] == '程序') {
    $sql=" `{$_GET['column']}` LIKE '%app%'";
  }elseif ($_GET['value'] == '单页') {
    $sql=" `{$_GET['column']}` LIKE '%web%'";
  }else{
    $sql=" `{$_GET['column']}` LIKE '%{$_GET['value']}%'";
  }
	$link='&my=search&column='.$_GET['column'].'&value='.$_GET['value'];
}else{
	$sql=" 1";
}
?>
      <table class="table table-bordered table-striped table-vcenter js-dataTable-full-pagination">
        <thead>
          <tr>
            <th class="text-center">编号</th>
            <th>项目名</th>
            <th class="d-none d-sm-table-cell">项目类型</th>
            <?php if($adminData['adminRank'] == 1) {?>
            <th class="d-none d-sm-table-cell" style="width: 15%;">审核状态</th>
            <?php }?>
            <th class="text-center" style="width: 15%;">操作</th>
          </tr>
        </thead>
        <tbody>
<?php
$pagesize=30;
$pages=ceil($numrows/$pagesize);
$page=isset($_GET['page'])?intval($_GET['page']):1;
$offset=$pagesize*($page - 1);

$rs=$DB->query("SELECT * FROM nteam_project_list WHERE{$sql} order by id limit $offset,$pagesize");
while($res = $rs->fetch())
{
?>
<tr>
<td class="text-center">
<?php echo $res['id'];?>
</td>
<td class="fw-semibold">
<?php echo $res['name'];?>
</td>
<td class="d-none d-sm-table-cell">
<?php echo ($res['type']=='web'?'单页':'程序');?>
</td>
<?php if($adminData['adminRank'] == 1) {?><?php echo '
<td class="d-none d-sm-table-cell">
'.($res['Audit_status']==1?'
<a href="javascript:setAudit_status('.$res['id'].',0)" class="btn btn-sm btn btn-success editable editable-click">
<b>已通过</b>
</a>
':'
<a href="javascript:setAudit_status('.$res['id'].',1)" class="btn btn-sm btn btn-danger editable editable-click">
<b>未通过</b>
</a>
')
.'
</td>
';?>
<?php }?>
<?php echo '
<td class="text-center">
<div class="btn-group">
<a href="./pset.php?my=edit&id='.$res['id'].'" class="btn btn-sm btn-secondary">
<i class="fa fa-pen-to-square"></i>
</a>
<a href="javascript:delProject('.$res['id'].')" class="btn btn-sm btn-secondary">
<i class="fa fa-xmark"></i>
</a>
</div>
</td>
</tr>';
}
?>
        </tbody>
      </table>
<?php
echo'<div class="dataTables_paginate paging_full_numbers d-flex justify-content-center"><ul class="pagination">';
$first=1;
$prev=$page-1;
$next=$page+1;
$last=$pages;
if ($page>1)
{
echo '<li class="paginate_button page-item next"><a class="page-link" href="javascript:void(0)" onclick="listTable(\'page='.$first.$link.'\')"><i class="fa fa-angle-double-left"></i></a></li>';
echo '<li class="paginate_button page-item last"><a class="page-link" href="javascript:void(0)" onclick="listTable(\'page='.$prev.$link.'\')"><i class="fa fa-angle-left"></i></a></li>';
} else {
echo '<li class="paginate_button page-item next"><a class="page-link"><i class="fa fa-angle-double-left"></i></a></li>';
echo '<li class="paginate_button page-item last"><a class="page-link"><i class="fa fa-angle-left"></i></a></li>';
}
$start=$page-10>1?$page-10:1;
$end=$page+10<$pages?$page+10:$pages;
for ($i=$start;$i<$page;$i++)
echo '<li class="paginate_button page-item"><a class="page-link" href="javascript:void(0)" onclick="listTable(\'page='.$i.$link.'\')">'.$i .'</a></li>';
echo '<li class="paginate_button page-item active"><a class="page-link">'.$page.'</a></li>';
for ($i=$page+1;$i<=$end;$i++)
echo '<li class="paginate_button page-item "><a  class="page-link" href="javascript:void(0)" onclick="listTable(\'page='.$i.$link.'\')">'.$i .'</a></li>';
if ($page<$pages)
{
echo '<li class="paginate_button page-item next"><a class="page-link" href="javascript:void(0)" onclick="listTable(\'page='.$next.$link.'\')"><i class="fa fa-angle-right"></i></a></li>';
echo '<li class="paginate_button page-item last"><a class="page-link" href="javascript:void(0)" onclick="listTable(\'page='.$last.$link.'\')"><i class="fa fa-angle-double-right"></i></a></li>';
} else {
echo '<li class="paginate_button page-item next"><a class="page-link"><i class="fa fa-angle-right"></i></a></li>';
echo '<li class="paginate_button page-item last"><a class="page-link"><i class="fa fa-angle-double-right"></i></a></li>';
}
echo'</ul></div>';