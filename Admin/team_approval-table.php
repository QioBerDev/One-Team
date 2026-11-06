<?php
$mod = 'admin';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}

if(isset($_GET['value']) && !empty($_GET['value'])) {
	if ($_GET['column'] == 1) {
		$sql=" Audit_status=0";
    $numrows=$DB->getColumn("SELECT count(*) from nteam_team_member WHERE{$sql}");
	}else{
		$sql=" `{$_GET['column']}` LIKE '%{$_GET['value']}%' and Audit_status=0";
    $numrows=$DB->getColumn("SELECT count(*) from nteam_team_member WHERE{$sql}");
	}
	$link='&my=search&column='.$_GET['column'].'&value='.$_GET['value'];
}else{
  $numrows=$DB->getColumn("SELECT count(*) from nteam_team_member WHERE{$sql}");
	$sql=" Audit_status=0";
}
?>
      <table class="table table-bordered table-striped table-vcenter js-dataTable-full-pagination">
        <thead>
          <tr>
            <th class="text-center">编号</th>
            <th>账号</th>
            <th class="d-none d-sm-table-cell">QQ</th>
            <th class="d-none d-sm-table-cell" style="width: 15%;">描述</th>
            <th class="text-center" style="width: 15%;">操作</th>
          </tr>
        </thead>
        <tbody>
<?php
$pagesize=30;
$pages=ceil($numrows/$pagesize);
$page=isset($_GET['page'])?intval($_GET['page']):1;
$offset=$pagesize*($page - 1);

$rs=$DB->query("SELECT * FROM nteam_team_member WHERE{$sql} order by id limit $offset,$pagesize");
while($res = $rs->fetch())
{
echo '
<tr>
<td class="text-center">
'.$res['id'].'
</td>
<td class="fw-semibold">
'.$res['name'].'
</td>
<td class="d-none d-sm-table-cell">
'.$res['qq'].'
</td>
<td class="d-none d-sm-table-cell">
'.$res['describe'].'
</td>
<td class="text-center">
<a href="javascript:setStatus('.$res['id'].',1)" class="btn btn-sm btn btn-success">
<b>通过</b>
</a>
&nbsp;
<a href="javascript:delTeam('.$res['id'].')" class="btn btn-sm btn btn-danger">
<b>删除<b>
</a>
</td>
</tr>
';
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