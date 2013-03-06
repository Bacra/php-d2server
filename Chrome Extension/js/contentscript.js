if (document.head.innerHTML.indexOf('<!-- UPDATA BY .BuildConfig Now:') != -1) {
	chrome.extension.sendRequest({}, function(response) {});
}