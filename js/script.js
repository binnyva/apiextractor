function init() {
    JSL.dom(".code").each(function(ele){
		var show_code = document.createElement("a");
		JSL.dom(show_code).click(function(e) {
			if(this.innerHTML.indexOf("show")+1) {
				JSL.dom(ele).show();
				JSL.dom(this).addClass("active");
				this.innerHTML = "[ hide code ]";
			} else {
				JSL.dom(ele).hide();
				JSL.dom(this).removeClass("active");
				this.innerHTML = "[ show code ]";
			}
		});
		show_code.className = "show-code";
		show_code.appendChild(document.createTextNode("[ show code ]"));
		
		ele.parentNode.insertBefore(document.createElement("br"), ele);
	    ele.parentNode.insertBefore(show_code, ele);
    })
}
JSL.dom(window).load(init);
 
