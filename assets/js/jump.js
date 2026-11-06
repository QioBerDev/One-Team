function moyi(){
    //获取文档对象
    let el = document.documentElement;
    //获取目标要锚点跳转的div
	let st = document.getElementById('moyi');
    //将该div的高度赋给当前浏览的滚动条高度
	el.scrollTop = st.offsetTop;
}