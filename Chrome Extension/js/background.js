// 监视url
chrome.tabs.onUpdated.addListener(function(tabId, changeInfo, tab) {
	if (tab.url.indexOf('file://') > -1) {
		chrome.pageAction.show(tabId);
		chrome.pageAction.setTitle({
			'tabId': tabId,
			'title': 'Catch me to localhost'
		});
	}
});

// 按钮事件
chrome.pageAction.onClicked.addListener(function(tab){
	var url = tab.url;
	if (url.indexOf('file://') > -1){
		chrome.tabs.update(tab.id, {'url': 'http://www.test.com/' + url.substr(11)});
	} else {
		chrome.tabs.update(tab.id, {'url': url.replace(/#.*/g, '') + '#!watch'});
	}
});






// 接受信息
chrome.extension.onRequest.addListener(function(request, sender, sendResponse) {
	chrome.pageAction.show(sender.tab.id);
	chrome.pageAction.setTitle({
		'tabId': sender.tab.id,
		'title': 'Catch this Page'
	});
});
