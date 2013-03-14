chrome.extension.sendMessage({
	'cmd': 'setPageAction',
	'status': 'catchPage'
});



// 连接WebSocket服务器
var _WS_SetTimeout4less = false,
	_WS_SetTimeout4reload = false,
	_WS_Interval = 1000,
	_WS,
	initWebSocket = (function(){
		_WS = new WebSocket('ws://www.test.com:8384/');

		_WS.addEventListener('open', function (evt) {
			insertScript('less.watchMode = false;');
			_WS.addEventListener('close', function(evt){
				insertScript('less.watchMode = true;');
				chrome.extension.sendMessage({
					'cmd': 'websocketShutDown'
				});
			});


			var path = window.location.pathname;
			_WS.send('D:/Projects' + path.substr(0, path.lastIndexOf('/')+1));


			console.log("Contact WebSocket Server");
			chrome.extension.sendMessage({
				'cmd': 'setPageAction',
				'status': 'conn-success'
			});
		});

		_WS.addEventListener('message', function (evt) {
			var data = JSON.parse(evt.data);

			switch (data.cmd) {
				case 'fileEvent':
					if (data.filename.indexOf('.less') > 0 && data.event == 'change') {
						if (!_WS_SetTimeout4less) {
							_WS_SetTimeout4less = true;
							
							insertScript('window.less.watchFN();');

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
					break;
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


		var thisFn = arguments.callee;

		return function(){
			if (!_WS || _WS.readyState == 2 || _WS.readyState == 3) {
				thisFn();
			}
		};
	})();



chrome.extension.onMessage.addListener(function(request, sender, sendResponse) {
	switch(request.cmd) {
		case 'initWebSocket':
			initWebSocket();
			break;
	}
	sendResponse(null);
});

function insertScript(code) {
	var script = document.createElement('script');
	script.type = 'text/javascript';
	script.textContent = code;
	document.head.appendChild(script);
}