// ==UserScript==
// @name           Mantis
// @namespace      Mantis
// @include        http://@TAG_CODEVTT_IP@/mantis/view.php?id=*
// @version        0.1
// ==/UserScript==

var codevtt_ip = location.hostname 
var elemID = document.evaluate("//tr[@class='row-1']/td",document,null,9,null).singleNodeValue;
elemID.innerHTML = "<a href='http://"+codevtt_ip+"/codevtt/reports/issue_info.php?bugid="+elemID.textContent+"' title='show in CodevTT'>"+elemID.textContent+"</a>";
