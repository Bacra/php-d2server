// node D:\node\test
var fs = require('fs'),
	ui = require('url'),
	ws = require('websocket.io'),
	wsPort = 8384;





//创建socket
var socket = ws.listen(wsPort);
console.log('== WebSocket Start:'+wsPort+' ==');

socket.on('connection', function(client){
	client.id = addWatch.watchClients.push([]);
	console.log('** Client' + client.id + ' **');

	client.on('message',function(data){
		console.log('## ' + data);
		if (data) addWatch(data, client);
	});

	client.on('close',function(){
		var filenames = addWatch.watchClients[client.id - 1];
		for (var i = filenames.length; i--;) {
			popArray(client, filenames[i]);
		}
		console.log('XX Client' + client.id + ' XX');
	});
});



function addWatch(path, client) {		// 添加的入口
	path = path.replace('\\', '/').toLowerCase();

	fs.exists(path, function(exists) {
		if (!exists) {
			console.warn(path+' not found!!');
			return;
		}
		fs.stat(path, function(err, stats) {
			if (stats.isFile()) {
				addWatch.addFile(path, client);
			} else {
				addWatch.addPath(path.replace(/\/$/, ''), client);
			}
		});
	});
}

addWatch.addPath = function(path, client) {
	if (path.indexOf('/.temp') != -1 || path.indexOf('/.git') != -1) return;
	
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
						addWatch.addFile(p, client);
					} else {
						addWatch.addPath(p, client);
					}
				});
			})();
		}
	});
};



addWatch.addFile = function(file, client) {		// 虽然fs.watch可以直接监视目录，但为了后期判断维护方便，全部通过遍历来实现添加文件
	if (file.indexOf('.gitignore') != -1) return;

	// 只做简单的排除，并不保证唯一
	if (addWatch.watchFiles[file]) {
		if (!inArray(client, addWatch.watchFiles[file])) {
			addWatch.addClient(client, file);
		}
		return;
	}
	
	addWatch.watchFiles[file] = [client];
	fs.watch(file, function(e, filename) {
		if (filename && e) {
			if (!addWatch.fileStatus[file]) {		// 等待缓存
				addWatch.fileStatus[file] = true;

				addWatch.broadcastMsg({
					'cmd': 'fileEvent',
					'file': file,
					'filename': filename,
					'event': e
				}, addWatch.watchFiles[file]);

				console.log('=> ' + file + '['+e+']');

				setTimeout(function(){
					addWatch.fileStatus[file] = false;
				}, addWatch.intervalSend);
			}
		} else {
			console.warn('[ERROR]filename not provided');
		}
	});
};

addWatch.addClient = function(client, file) {
	addWatch.watchFiles[file].push(client);
	addWatch.watchClients[client.id - 1].push(file);
};

addWatch.broadcastMsg = function(msg, clis) {
	for (var i = clis.length; i--;) {
		clis[i].send(JSON.stringify(msg));
	}
	addWatch.broadcastMsgList.push(msg);
};

addWatch.fileStatus = {};		// 缓存文件状态（可能一次文件保存操作会出发几次事件）
addWatch.watchFiles = {};
addWatch.watchClients = [];
addWatch.intervalSend = 400;
addWatch.broadcastMsgList = [];




function popArray(ob, arr){
	var arr2 = [];
	for(var i = arr.length; i--;) {
		if (ob !== arr[i]) arr2.push(arr[i]);
	}
	return arr2;
}

function inArray(ob, arr){
	for (var i = arr.length; i--;) {
		if (ob === arr[i]) return true;
	}
	return false;
}