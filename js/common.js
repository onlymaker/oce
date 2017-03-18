function setCookie(name, value, days) {
    days = (value === null) ? 0 : (days || 30);
    var date = new Date();
    var time = !!days ? (date.getTime() + days * 24 * 60 * 60 * 1000) : (date.getTime() - 1);
    date.setTime(time);
    document.cookie = name + "=" + encodeURIComponent(value) + ";expires=" + date.toUTCString() + ";path=/";
}

function getCookie(name) {
    var data = document.cookie.match(new RegExp("(^| )" + name + "=([^;]*)(;|$)"));
    return !!data ? decodeURIComponent(data[2]) : '';
}

function deleteCookie(name) {
    setCookie(name, null);
}