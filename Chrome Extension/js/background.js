// 监视url
chrome.tabs.onUpdated.addListener(function(tabId, changeInfo, tab) {
	if (tab.url.indexOf('file://') > -1) {
		chrome.pageAction.show(tabId);
		chrome.pageAction.setTitle({
			'tabId': tabId,
			'title': 'Catch me to localhost'
		});
		chrome.pageAction.setIcon({
			'tabId': tabId,
			'path': '../icon/icon-19.png'
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



chrome.extension.onMessage.addListener(function(request, sender, sendResponse) {
	var sendTabId = sender.tab.id;
	switch(request.cmd) {
		case 'reload':
			chrome.tabs.reload(sendTabId);
			break;
		case 'setPageAction':
			chrome.pageAction.show(sendTabId);

			var title, icon;
			switch(request.status) {
				case 'catchPage':
					title = 'Catch this Page';
					icon = 'ws-conn';
					break;
				case 'conn-success':
					title = 'Contact WebSocket Server';
					icon = 'ws-norm';
					break;
				case 'conn-error':
					title = 'Discontact WebSocket Server';
					icon = 'ws-error';
					break;
				case 'conn-close':
					title = 'WebSocket Server Closed';
					icon = 'ws-disconn';
					break;
			}
			chrome.pageAction.setTitle({
				'tabId': sendTabId,
				'title': title
			});
			chrome.pageAction.setIcon({
				'tabId': sendTabId,
				'path': '../icon/'+icon+'-19.png'
			});
			break;
	}
	sendResponse(null);
});
