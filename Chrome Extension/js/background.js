var _WebsocketWaitQuery = {
		'list': [],
		'add': function(tabId) {
			this.list.push(tabId);
		},
		'remove': function(tabId) {
			var index = this.index(tabId);
			if (index != -1) this.list.splice(index, 1);
		},
		'index': function(tabId) {
			for (var i = this.list.length; i--;) {
				if (tabId == this.list[i]) return i;
			}
			return -1;
		},
		'map': function(callback) {
			var arr = this.list.slice();		// 复制一份，防止出现同步问题
			for(var i = arr.length; i--;) {
				callback(arr[i]);
			}
		},
		'reload': function() {
			this.map(function(tabId){
				chrome.tabs.reload(tabId);
			});
		},
		'sendMsg': function(msg) {
			this.map(function(tabId){
				chrome.tabs.sendMessage(tabId, msg);
			});
		}
	},
	contactWebkSocket = function() {
		_WebsocketWaitQuery.map(function(tabId){
			chrome.pageAction.setTitle({
				'tabId': tabId,
				'title': 'Contact WebSocket Server...'
			});
			chrome.pageAction.setIcon({
				'tabId': tabId,
				'path': '../icon/icon-19.png'
			});
		});
		_WebsocketWaitQuery.sendMsg({'cmd': 'initWebSocket'});
	};


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
		_WebsocketWaitQuery.reload();
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
					title = 'Just Catch this Page';
					icon = 'icon';
					break;
				case 'conn-success':
					title = 'Contact WebSocket Server';
					icon = 'ws-conn';
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
		case 'websocketShutDown':
		case 'websocketError':
			var myTime = new Date(),
				notification = webkitNotifications.createNotification("", "与WebSocket Server断开 ", "WebSocket Server已经退出，重启之后请点击此对话框刷新相关页面（"+ myTime.getHours() + ":" + myTime.getMinutes() + ":" + myTime.getSeconds()+"）");

			notification.onclick = function(){
				contactWebkSocket();
				notification.cancel();
				notification = null;
			};

			notification.replaceId = 'webProjectBG_serverError';
			notification.show();
			break;
		case 'joinWebsocketWaitQuery':
			_WebsocketWaitQuery.add(sendTabId);
			break;

	}
	sendResponse(null);
});


// 监听update事件
chrome.tabs.onUpdated.addListener(function(tabId){
	_WebsocketWaitQuery.remove(tabId);
});
chrome.tabs.onRemoved.addListener(function(tabId){
	_WebsocketWaitQuery.remove(tabId);
});