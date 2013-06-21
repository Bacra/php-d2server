/*var __PAGE_URL__ = 'http://www.test.com/8684square/index2.html',
	__PAGE_OMITS__ = {
		'wf_ddfeg': 'name1',
		'feg': 'name2'
	};*/


if (window.addEventListener) {
	;(function(omits, div, undefined){
		window.addEventListener('keydown', function(e){
			if (e.keyCode == 192){
				if (div) {
					div.style.display = div.clientWidth ? 'none' : 'block';
				} else {
					buildDiv();
				}
			}
		}, false);

		function buildDiv(){
			div = document.createElement('div');
			div.style.position = 'fixed';
			div.style.top = '30%';
			div.style.left = '50%';
			div.style.marginLeft = '-150px';
			div.style.width = '300px';
			div.style.padding = '20px';

			div.style.border = '#666 3px solid';
			div.style.boxShadow = '3px 3px 10px #ccc';
			div.style.borderRadius = '4px';
			div.style.background = '#fff';
			
			div.style.textAlign = 'left';
			div.style.fontSize = '12px';
			div.style.color = '#333';
			div.style.lineHeight = '1.4';


			var pathname = window.location.pathname,
				query = getUrlQuery(pathname),
				params = query.params,
				selectElem, checkboxElem,
				innerHTML = '<em style="position: absolute; top: 4px; right: 8px; cursor: pointer; font-size: 16px;">×</em><p><label><input type="checkbox" style="margin-right: 4px;" />显示原始的资源文件</label></p>';


			if (!omits) {
				div.innerHTML = innerHTML + '<p style="padding-top: 8px;">缺少必要的参数，请检查项目配置文件</p>';
			} else {
				div.innerHTML = innerHTML + '<p style="padding-top: 8px;">请选择跳转页面：' + cearteSelect()+'</p>';
			}

			selectElem = div.getElementsByTagName('select')[0];
			if (selectElem) {
				selectElem.onchange = function(){
					if (this.value) {
						params.omit = this.value;
					} else if (params.omit) {
						delete params.omit;
					}
					window.location.href = mergerQuery(query.uri, params);
				};

				if (params.omit) selectElem.value = params.omit;
			}

			checkboxElem = div.getElementsByTagName('input')[0];
			if (checkboxElem) {
				if (params.merger)	{
					checkboxElem.checked = true;
					checkboxElem.onchange = function(){
						delete params.merger;
						window.location.href = mergerQuery(query.uri, params);
					};
				} else {
					checkboxElem.onchange = function(){
						params.merger = 1;
						window.location.href = mergerQuery(query.uri, params);
					};
				}
			}

			div.getElementsByTagName('em')[0].onclick = function(){
				div.style.display = 'none';
			};

			document.body.appendChild(div);
		}


		function cearteSelect(){
			var str = ['<select><option value="">所有模块</option>'];
			for(var i in omits) {
				str.push('<option value="'+i+'">'+omits[i]+'</option>');
			}
			str.push('</select>');

			return str.join('');
		}

		function getUrlQuery(url){
			var arr = url.split('--'),
				uri = arr[0],
				params = {};

			for (var i = arr.length, temp; i-- && i > 0;) {
				if (arr[i] == 'merger') {
					params.merger = 1;
				} else {
					temp = arr[i].match(/omit_(\w+)/);
					if (temp) params.omit = temp[1];
				}
			}

			return {
				'uri': uri,
				'params': params
			};
		}

		function mergerQuery(uri, params){
			var mergerStr = '', omitStr = '';
			for(var i in params) {
				if (i == 'merger') {
					mergerStr = '--merger';
				} else if (i == 'omit') {
					omitStr = '--omit_'+params[i];
				}
			}
			return uri+omitStr+mergerStr;
		}
	})(window.__PAGE_OMITS__);
}