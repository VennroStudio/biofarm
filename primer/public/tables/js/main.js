function modalOpen(header, body, callback) {
    document.getElementById('wrap').style.display = 'block';
    document.getElementById('modal').style.display = 'block';

    if (null !== header) {
        document.getElementById('modal-header').innerHTML =  unescape(header);
    }

    document.getElementById('modal-body').innerHTML = body;

    if (undefined !== callback) {
        callback();
    }
}

function modalClose() {
    document.getElementById('wrap').style.display = 'none';
    document.getElementById('modal').style.display = 'none';
}

function loader(status) {
    document.getElementById('wrap').style.display = status === 1 ? 'block' : 'none';
}

function timeConverter(timestamp){
    const a = new Date(timestamp * 1000);
    const year = a.getFullYear();
    const month = pad(a.getMonth() + 1);
    const date = pad(a.getDate());
    const hour = pad(a.getHours());
    const min = pad(a.getMinutes());
    return date + '.' + month + '.' + year + ' ' + hour + ':' + min;
}

function pad(n){
    return n < 10 ? '0' + n : n
}
