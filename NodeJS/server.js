// node D:\node\test
var fs = require('fs');
var http = require('http');
var ui = require('url');
var ws = require('websocket.io');
var watchFiles = {};
var watchFileTimeoutStatus = {};
var clients = [];
var intervalSend = 400;


var server = http.createServer(function(req, res){ 
	res.writeHead(200,{ 'Content-Type': 'text/html' }); 
	res.end('<h1>Hello Socket!</h1>');
});
server.listen(8080);

//创建socket
var socket = ws.listen(server);
socket.on('connection', function(client){
	client.on('message',function(data){
		console.log('[Received]', data);
		if (data) addWatch(data, client);
	});

	client.on('disconnect',function(){
		var filenames = clients[clients.id - 1];
		for (var i = filenames.length; i--;) {
			popArray(client, filenames[i]);
		}
		console.log('[Disconnect]' + client.id);
	});
});


/*server = http.createServer(function (req, res) {
	var param = ui.parse(req.url, true).query;
	console.log(param);
	if (param.filename) addWatch(param.filename);

	res.writeHeader(200, {"Content-Type": "text/json"});
	res.end('{}');
});
server.listen(8000);  
console.log("httpd start @8000");*/


function addWatch(path, client) {		// 添加的入口
	path = path.replace('\\', '/').toLowerCase();

	// 只做简单的排除，并不保证唯一
	if (watchFiles[path]) {
		if (!inArray(client, watchFiles[path])) {
			addClient(client, path);
		}
		return;
	}

	fs.exists(path, function(exists) {
		if (!exists) {
			console.warn(path+' not found!!');
			return;
		}
		fs.stat(path, function(err, stats) {
			if (stats.isFile()) {
				addWatchFile(path, client);
			} else {
				addWatchPath(path.replace(/\/$/, ''), client);
			}
		});
	});
}

function addWatchPath(path, client) {
	fs.readdir(path, function(err, paths) {
		// files为目录里的文件及子目录（不包含子目录里的内容）
		if (err) {
			console.warn('[ERROR]'+path+' can not read');
			return;
		}

		for (var i = paths.length; i--;) {
			;(function(){
				var p = path+'/'+paths[i];
				fs.stat(p, function (err, stats) {
					if (err) {
						console.warn('[ERROR]'+p+' can not read');
						return;
					}
					if (stats.isFile()) {
						addWatchFile(p, client);
					} else {
						addWatchPath(p, client);
					}
				});
			})();
		}
	});
}

function addWatchFile(file, client) {		// 虽然fs.watch可以直接监视目录，但为了后期判断维护方便，全部通过遍历来实现添加文件
	watchFiles[file] = [client];
	fs.watch(file, function(e, filename) {
		if (filename && e) {
			if (!watchFileTimeoutStatus[file]) {		// 等待缓存
				watchFileTimeoutStatus[file] = true;
				broadcastClient('{"file": "'+file+'", "filename": "'+filename+'", "event": "'+e+'"}', watchFiles[file]);
				console.log('=> ' + file);

				setTimeout(function(){
					watchFileTimeoutStatus[file] = false;
				}, intervalSend);
			}
		} else {
			console.warn('[ERROR]filename not provided');
		}
	});
}


function addClient(client, path) {
	watchFiles[path].push(client);
	if (client.id) {
		clients[client.id - 1].push(path);
	} else {
		client.id = clients.push([path]);
	}
}


function popArray(ob, arr){
	var arr2 = [];
	for(var i = arr.length; i--;) {
		if (client !== arr[i]) arr2.push(arr[i]);
	}
	return arr2;
}

function inArray(ob, arr){
	for (var i = arr.length; i--;) {
		if (ob === arr[i]) return true;
	}
	return false;
}

function broadcastClient(data, clis) {
	for (var i = clis.length; i--;) {
		clis[i].send(data);
	}
}