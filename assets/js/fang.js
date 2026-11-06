// 禁止鼠标左右键
document.addEventListener('contextmenu', function(e) {
   e.preventDefault();
 });
 
 // 禁止iframe镜像
 if (self != top) {
   top.location.href = self.location.href;
 }
 
 // 禁用F1-F12键
 document.addEventListener('keydown', function(e) {
   if (e.keyCode >= 112 && e.keyCode <= 123) {
     e.preventDefault();
   }
 });
 
 // 禁止ctrl组合键
 document.addEventListener('keydown', function(e) {
   if (e.ctrlKey) {
     e.preventDefault();
   }
 });