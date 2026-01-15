chrome.declarativeNetRequest.onRuleMatchedDebug.addListener((e) => {
    const msg = `Request to ${e.request.url} on tab ${e.request.tabId}.`;
    console.log(msg);
});

console.log('Service worker started.');


