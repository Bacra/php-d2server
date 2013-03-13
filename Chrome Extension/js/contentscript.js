chrome.extension.sendMessage({
	'cmd': 'setPageAction',
	'status': 'catchPage'
});



// 连接WebSocket服务器
var _WS_SetTimeout4less = false,
	_WS_SetTimeout4reload = false,
	_WS_Interval = 1000,
	_WS = new WebSocket('ws://www.test.com:8080/');

_WS.addEventListener('open', function (evt) {
	console.log("Contact WebSocket Server");

	_WS.addEventListener('close', function(evt){
		chrome.extension.sendMessage({
			'cmd': 'websocketShutDown'
		});
	});


	var path = window.location.pathname;
	_WS.send('D:/Projects' + path.substr(0, path.lastIndexOf('/')+1));

	chrome.extension.sendMessage({
		'cmd': 'setPageAction',
		'status': 'conn-success'
	});
});

_WS.addEventListener('message', function (evt) {
	var json = JSON.parse(evt.data);
	if (json.filename.indexOf('.less') != -1) {
		if (!_WS_SetTimeout4less) {
			_WS_SetTimeout4less = true;
			
			var script = document.createElement('script');
			script.type = 'text/javascript';
			script.textContent = 'window.less.watchFN();';
			document.head.appendChild(script);

			setTimeout(function(){
				_WS_SetTimeout4less = false;
			}, _WS_Interval);
		}
	} else {
		if (!_WS_SetTimeout4reload) {
			_WS_SetTimeout4reload = true;
			_WS_SetTimeout4less = true;

			// window.reload();		// 只刷新当前tab
			chrome.extension.sendMessage({'cmd': 'reload'});

			setTimeout(function(){
				_WS_SetTimeout4reload = false;
				_WS_SetTimeout4less = false;
			}, _WS_Interval);
		}
	}
});

_WS.addEventListener('error', function (evt) { 
	console.log('WebSocket Error occured: ' + evt.data);
	chrome.extension.sendMessage({
		'cmd': 'websocketError'
	});
});


_WS.addEventListener('close', function (evt) {
	console.log("Discontacte WebSocket Server");
	chrome.extension.sendMessage({
		'cmd': 'setPageAction',
		'status': 'conn-close'
	});
	chrome.extension.sendMessage({
		'cmd': 'joinWebsocketWaitQuery'
	});
});