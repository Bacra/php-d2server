// node D:\node\test
var fs = require('fs'),
	ui = require('url'),
	ws = require('websocket.io'),
	http = require('http'),
	parseURL = require('url').parse;
	port = 8080,
	server = http.createServer(function(req, res) {
		if (req.url != '/favicon.ico') {
			var url = parseURL(req.url, true);
			if (url.pathname == '/json') {
				res.writeHead(200, {'Content-Type': 'text/json'});
				var sendJSON;
				switch(url.query.con) {
					case 'watchFiles':
						sendJSON = {
							'data': watchFile4Socket.watchFiles
						};
						break;
					case 'log':
						sendJSON = {
							'data': watchFile4Socket.fileLog
						};
						break;
					case 'client':
						sendJSON = {
							'sum': watchFile4Socket.clientIndex,			// 总共连接数据
							'actived': watchFile4Socket.clientNum			// 当前正在通讯的数目
						};
						break;
					default:
						sendJSON = {};
				}
				sendJSON.status = 0;
				res.write(JSON.stringify(sendJSON));
			} else {
				var watchFileLogHtml = ['<table border="1px">'],
					log;
				for (var i = watchFile4Socket.fileLog.length; i--;) {
					log = watchFile4Socket.fileLog[i];
					watchFileLogHtml.push('<tr><td>'+log.file+'</td><td>'+log.filename+'</td><td>'+new Date(log.timestamp)+'</td></tr>');
				}
				watchFileLogHtml.push('</table>');

				res.writeHead(200, {'Content-Type': 'text/html'});
				res.write('<!DOCTYPE HTML><html><head><meta charset="utf-8" /><title>Server 运行状态</title><script type="text/javascript">var _RefreshTimeout;function refreshPage() {_RefreshTimeout = setTimeout(function() {window.location.reload();}, 2000);}refreshPage();window.onscroll = function() {window.location.hash = "#" + window.scrollY;};window.onfocus = function() {refreshPage();};window.onblur = function() {clearTimeout(_RefreshTimeout);};if (window.location.hash) {window.scrollTo(0, Number(window.location.hash.substring(1)));}</script></head><body><div>连接总数：'+watchFile4Socket.clientIndex+' 当前活动连接：'+watchFile4Socket.clientNum+'</div><h3>监视的文件</h3>\n'+watchFile4Socket.watchFiles.join('<br />')+'<h3>文件记录</h3>\n'+watchFileLogHtml.join('')+'</body></html>');
			}
		}
		res.end();
	});



function watchFile4Socket(client, files, paths) {		// 添加的入口
	var i;

	// 先处理files中的内容（优先级比目录高）
	for(i = files.length; i--;) {
		;(function(){
			var file = files[i];
			fs.exists(file, function(exists) {
				if (!exists) {
					console.warn(file+' not found!!');
					return;
				}
				watchFile4Socket.addFile(client, file);
			});
		})();
	}

	for (i = paths.length; i--;) {
		;(function(){
			var path = paths[i].path.replace('\\', '/'),
				ignore = paths[i].ignore;
			fs.exists(path, function(exists) {
				if (!exists) {
					console.warn(path+' not found!!');
					return;
				}
				// 编译ignore列表
				var new_ignore = [];

				for (var ii = ignore.length; ii--;) {
					new_ignore.push(ignore[ii].indexOf('regexp:') === 0 ? watchFile4Socket.string2regexp(ignore[ii].substring(7)) : watchFile4Socket.wildcard2regexp(ignore[ii]));
				}

				watchFile4Socket.addPath(client, path, path.length, new_ignore);
			});
		})();
	}
}

watchFile4Socket.addPath = function(client, path, rootPathLength, ignore) {
	fs.readdir(path, function(err, paths) {
		// files为目录里的文件及子目录（不包含子目录里的内容）
		if (err) {
			console.warn('[ERROR]'+path+' can not read');
			return;
		}

		for (var i = paths.length; i--;) {
			;(function(){
				var p = path+paths[i];
				fs.stat(p, function (err, stats) {
					if (err) {
						console.error('[ERROR]'+p+' can not read');
						return;
					}

					var isFile = stats.isFile();

					if (!isFile) p += '/';

					// 检查处理排除列表
					if (watchFile4Socket.mapTest(p.substring(rootPathLength), ignore)) {
						console.warn('[WARN] '+p+' ignore');
						return;
					}

					if (isFile) {
						watchFile4Socket.addFile(client, p);
					} else {
						watchFile4Socket.addPath(client, p, rootPathLength, ignore);
					}
				});
			})();
		}
	});
};


watchFile4Socket.mapTest = function(str, patterns) {
	for (var i = patterns.length; i--;) {
		// console.log('patterns', patterns[i]);
		if (patterns[i].test(str)) return true;
	}
	return false;
};

watchFile4Socket.wildcard2regexp = function(pattern) {
	pattern = pattern.replace(/\./g, "\\.");
	pattern = pattern.replace(/\*/g, ".*");
	pattern = pattern.replace(/\?/g, ".");
	return new RegExp("^" + pattern + "$");
};
watchFile4Socket.string2regexp = function(pattern) {
	console.log(pattern);
	var arr = pattern.match(/^\/(.+)\/([igm]*)$/);
	return new RegExp(arr[1], arr[2]);
};


// 虽然fs.watch可以直接监视目录，但为了后期判断维护方便，全部通过遍历来实现添加文件
// 文件的排除放在path里面检测
watchFile4Socket.addFile = function(client, file) {
	
	// 只做简单的排除，并不保证唯一
	if (watchFile4Socket.watchFileClients[file]) {
		if (watchFile4Socket.indexClient(client, watchFile4Socket.watchFileClients[file]) == -1) {
			watchFile4Socket.addWatch(client, file);
		}
		return;
	}
	
	// 文件存储的数组变量初始化
	watchFile4Socket.watchFileClients[file] = [];
	watchFile4Socket.watchFiles.push(file);

	// 添加变量和主机的绑定
	watchFile4Socket.addWatch(client, file);

	fs.watch(file, function(e, filename) {
		if (filename && e) {
			if (!watchFile4Socket.cacheFileStatus[file]) {		// 等待缓存
				watchFile4Socket.cacheFileStatus[file] = true;

				watchFile4Socket.broadcastMsg({
					'cmd': 'fileEvent',
					'file': file,
					'filename': filename,
					'event': e
				}, watchFile4Socket.watchFileClients[file]);

				watchFile4Socket.fileLog.push({
					'file': file,
					'filename': filename,
					'event': e,
					'timestamp': new Date().getTime()
				});
				console.log('=> ' + file + '['+e+']');

				setTimeout(function(){
					watchFile4Socket.cacheFileStatus[file] = false;
				}, watchFile4Socket.intervalSend);
			}
		} else {
			console.warn('[ERROR] filename not provided');
		}
	});
};

watchFile4Socket.addWatch = function(client, file) {
	watchFile4Socket.watchFileClients[file].push(client);
	watchFile4Socket.watchClientFiles[client.uqId].push(file);
};

watchFile4Socket.broadcastMsg = function(msg, clis) {
	for (var i = clis.length; i--;) {
		console.log('broadcastMsg', clis[i].id);
		clis[i].send(JSON.stringify(msg));
	}
};

watchFile4Socket.popClientArray = function(client, arr){
	var index = watchFile4Socket.indexClient(client, arr);
	if (index != -1) arr.splice(index, 1);
};

watchFile4Socket.indexClient = function(client, arr){
	for (var i = arr.length; i--;) {
		if (client.uqId == arr[i].uqId) return i;
	}
	return -1;
};

watchFile4Socket.initClient = function(client){
	client.uqId = 'Client' + (++watchFile4Socket.clientIndex);
	watchFile4Socket.watchClientFiles[client.uqId] = [];
	watchFile4Socket.clientNum++;

	console.log('== LINK ' + client.uqId + ' ==');
};

watchFile4Socket.cacheFileStatus = {};		// 缓存文件状态（可能一次文件保存操作会出发几次事件）

watchFile4Socket.watchFileClients = {};
watchFile4Socket.watchClientFiles = {};
watchFile4Socket.watchFiles = [];
watchFile4Socket.clientIndex = 0;
watchFile4Socket.clientNum = 0;
watchFile4Socket.intervalSend = 400;
watchFile4Socket.fileLog = [];






server.listen(port);
console.log('== Server Start:'+port+' ==');

//创建socket
var socket = ws.attach(server);
socket.on('connection', function(client){
	watchFile4Socket.initClient(client);
	
	// 绑定相关事件
	client.on('message',function(data){
		if (!data) {
			console.log('!# [empty]');
			return;
		}
		// console.log(data);
		data = JSON.parse(data);
		switch (data.cmd) {
			case 'setClientInfo':
				client.htmlInfo = data.info;
				break;
			case 'watchFiles':
				watchFile4Socket(client, data.files, data.paths);
				break;
			default:
				console.log('!# ' + data);
		}
		
	});

	client.on('close',function(){
		var filenames = watchFile4Socket.watchClientFiles[client.uqId];
		for (var i = filenames.length; i--;) {
			watchFile4Socket.popClientArray(client, watchFile4Socket.watchFileClients[filenames[i]]);
		}
		watchFile4Socket.clientNum--;
		console.log('== UNLINK ' + client.uqId + ' ==');
	});
});