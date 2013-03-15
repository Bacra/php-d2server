// 按钮事件
var id = 'bkpcdngelgpfbpjlaioplcophekpdomc';
chrome.browserAction.onClicked.addListener(function(){
	chrome.management.setEnabled(id, false, function() {
		chrome.management.setEnabled(id, true);
	});
});