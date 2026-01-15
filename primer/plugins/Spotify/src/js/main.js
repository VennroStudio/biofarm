const API_URL = 'https://music.lo.media/v1/';
const API_KEY = 'nsOqnloLavTPjG0su607lZwMx2a2exX0E4Xeu4xfE6Cda4I7';

let isInit = false;
let seconds = 0;
let interval = 10;
let reLoginAfter = 20 * 60;

$(document).ready(function() {
    init().then();
    setInterval(main, interval * 1000);
});

function main() {

    seconds += interval;

    if (seconds % 60 === 0) {
        let text = 'time: ' + Math.round(seconds / 60) + ' min.';
        console.clear();
        console.log('%c ' + text, 'background: #7fff00; color: #000');
    }

    if (window.location.href === 'https://whisperify.net/') {
        $("button:contains('Login With Spotify')").click();
    }

    if (seconds >= reLoginAfter) {
        window.location.href = '/';
    }
}

async function init() {
    if (isInit === true) {
        return;
    }

    if (localStorage.getItem('mode') === null) {
        localStorage.setItem('mode', '0');
    }

    let mode = parseInt(localStorage.getItem("mode") ?? 0);

    if (mode !== 0) {
        chrome.runtime.sendMessage({action: "setTokenId", tokenId: mode}, function(response) {});
    }

    const response = await fetch(
        API_URL + 'spotify-tokens',
        {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'apiKey': API_KEY,
            },
        }
    );

    if (response.status !== 200) {
        console.log('ERROR!');
        return;
    }

    let responseData = await response.json();
    let items = responseData.data.items;

    let options = '';

    for (let i = 0; i < items.length; i++) {
        options += '<option value="' + items[i]['id'] + '" ' + (mode === items[i]['id'] ? 'selected' : '') + '>' + items[i]['id'] + ' — ' + items[i]['comment'] + '</option>';
    }

    let select = '\
        <select id="selectMode" style="color: #000;">\
            <option value="0" ' + (mode === 0 ? 'selected' : '') + '>- Не выбрано -</option>\
            ' + options + '\
        </select>\
    ';

    $('body').append('<div class="loNavigation">' + select + '</div>');

    $('#selectMode').on('change', function() {
        let val = $('#selectMode').val();
        localStorage.setItem('mode', val);
        chrome.runtime.sendMessage({action: "setTokenId", tokenId: val}, function(response) {});
    });

    isInit = true;
}
