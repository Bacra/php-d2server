// 监视url
chrome.tabs.onUpdated.addListener(function(tabId, changeInfo, tab) {
	if (tab.url.indexOf('file://') > -1) {
		chrome.pageAction.show(tabId);
	}
});

// 按钮事件
chrome.pageAction.onClicked.addListener(function(tab){
	if (tab.url.indexOf('file://') > -1){
		chrome.tabs.update(tab.id, {'url': tab.url.replace(/^file:\/\/\/\w:\//i, 'http://www.test.com/').replace(/#.*/g, '')});
	} else {
		chrome.tabs.update(tab.id, {'url': tab.url.replace(/#.*/g, '') + '#!watch'});
	}
});

// 接受信息
chrome.extension.onRequest.addListener(function(request, sender, sendResponse) {
	chrome.pageAction.show(sender.tab.id);
});
