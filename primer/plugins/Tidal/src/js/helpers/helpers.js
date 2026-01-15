function $_GET(key) {
    let p = window.location.search;
    p = p.match(new RegExp(key + '=([^&=]+)'));
    return p ? p[1] : false;
}
function scrollTop()
{
    window.scrollTo(0, 0);
}

function scrollSlow(elem)
{
    const offset = 0;

    $('html, body').animate({
        scrollTop: elem.offset().top - offset
    }, 100);

    return false;
}

function replacer(str)
{
    str = str.replace(/&amp;/gi, '&');
    str = str.replace(/&nbsp;/gi, ' ');

    return str;
}

function timeConverter(unixtime){
    const a = new Date(unixtime * 1000);
    const months = ['янв', 'фев', 'мар', 'апр', 'май', 'июнь', 'июль', 'авг', 'сен', 'окт', 'ноя', 'дек'];
    const month = months[a.getMonth()];
    return a.getDate() + ' ' + month + ' ' + a.getFullYear() + ' ' + a.getHours() + ':' + a.getMinutes();
}

function getTimestampInSeconds () {
    return Math.floor(Date.now() / 1000)
}

function convertToUnixTime(dateString) {
    const date = new Date(dateString);
    return Math.floor(date.getTime() / 1000);
}

function removeHtmlTags(text) {
    if (text === undefined) {
        return '';
    }
    return text.replace(/<br\s*[\/]?>/gi, '\n').replace(/<[^>]+>/g, '');

}

function isSubstringUrl(str) {
    let url = window.location.href;

    return url.indexOf(str) !== -1;
}

function random(min, max) {
    return Math.floor(Math.random() * (max - min + 1) + min)
}
