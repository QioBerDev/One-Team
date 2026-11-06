<?php
$mod = 'admin';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}

if(isset($_GET['value']) && !empty($_GET['value'])) {
	if ($_GET['column'] == 1) {
		$sql=" 1";
    $numrows=$DB->getColumn("SELECT count(*) from nteam_log WHERE{$sql}");
	}else{
		$sql=" `{$_GET['column']}` LIKE '%{$_GET['value']}%'";
    $numrows=$DB->getColumn("SELECT count(*) from nteam_log WHERE{$sql}");
	}
	$link='&my=search&column='.$_GET['column'].'&value='.$_GET['value'];
}else{
  $numrows=$DB->getColumn("SELECT count(*) from nteam_log WHERE 1");
	$sql=" 1";
}
?>
      <table class="table table-bordered table-striped table-vcenter js-dataTable-full-pagination">
        <thead>
          <tr>
            <th class="text-center">操作者</th>
            <th>操作内容</th>
            <th class="d-none d-sm-table-cell">操作IP</th>
            <th class="d-none d-sm-table-cell" style="width: 15%;">操作地点</th>
            <th class="text-center" style="width: 15%;">操作时间</th>
          </tr>
        </thead>
        <tbody>
<?php
$pagesize=30;
$pages=ceil($numrows/$pagesize);
$page=isset($_GET['page'])?intval($_GET['page']):1;
$offset=$pagesize*($page - 1);
$city = $Gets->get_city($ip);
$rs=$DB->query("SELECT * FROM nteam_log WHERE{$sql} order by id limit $offset,$pagesize");
while($res = $rs->fetch())
{
echo '
<tr>
<td class="text-center">
'.$res['adminUser'].'
</td>
<td class="fw-semibold">
'.$res['type'].'
</td>
<td class="d-none d-sm-table-cell">
'.$res['ip'].'
</td>
<td class="d-none d-sm-table-cell">
'.$res['city'].'
</td>
<td class="text-center">
'.$res['data'].'
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