if (document.head.innerHTML.indexOf('<!-- UPDATA BY') != -1) {
	chrome.extension.sendRequest({}, function(response) {});

	var _WS_SetTimeout4less = false,
		_WS_SetTimeout4reload = false,
		_WS_Interval = 1000,
		_WS = new WebSocket('ws://127.0.0.1:8080/');

	_WS.onopen = function (evt) {
		console.log("Connected to WebSocket server.");
		var path = window.location.pathname;
		_WS.send('D:/Projects' + path.substr(0, path.lastIndexOf('/')+1));
	};
	_WS.onclose = function (evt) { 
		console.log("WebSocket Disconnected");
	};
	_WS.onmessage = function (evt) {
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
				window.location.reload();

				setTimeout(function(){
					_WS_SetTimeout4reload = false;
					_WS_SetTimeout4less = false;
				}, _WS_Interval);
			}
		}
	};
	_WS.onerror = function (evt) { 
		console.log('WebSocket Error occured: ' + evt.data);
	};
}