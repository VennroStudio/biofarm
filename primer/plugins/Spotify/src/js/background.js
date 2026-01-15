chrome.webRequest.onBeforeSendHeaders.addListener(
    function(details) {
        tokenHandle(details).then();
    },
    {urls: ["*://api.spotify.com/*"]},
    ["requestHeaders"]
);

chrome.runtime.onMessage.addListener(function(request, sender, sendResponse) {
    if (request.action === 'setTokenId') {
        tokenId = request.tokenId;
    }
});

const API_URL = 'https://music.lo.media/v1/';
const API_KEY = 'nsOqnloLavTPjG0su607lZwMx2a2exX0E4Xeu4xfE6Cda4I7';

console.log('Spotify token handler started.');

let accessToken = null;
let tokenId = null;

async function tokenHandle(details) {

    if (null === tokenId) {
        console.log('TOKEN ID NOT SELECTED');
        return;
    }

    if (details.initiator !== 'https://whisperify.net') {
        return;
    }

    let idx = 3;

    if (details.requestHeaders[idx].name !== 'Authorization') {
        return;
    }

    let words = details.requestHeaders[idx].value.split(' ');

    if (words[0] !== 'Bearer') {
        return;
    }

    let token = words[1];

    if (token !== accessToken) {
        accessToken = token;
        let isOk = await refreshToken(tokenId, accessToken);
        console.log(isOk);
    }
}

async function refreshToken(id, accessToken) {

    try {
        let result = await fetch(API_URL + 'spotify-tokens/' + id, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'apiKey': API_KEY,
            },
            body: JSON.stringify({
                accessToken: accessToken
            })
        });

        result = await result.json();

        if (result?.data?.success === 1) {
            return true;
        }

    } catch (e) {}

    return false;
}

