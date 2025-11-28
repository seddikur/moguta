// js переменные из движка
var mgBaseDir = '',
    mgNoImageStub = '',
    protocol = '',
    phoneMask = '',
    sessionToDB = '',
    sessionAutoUpdate = '',
    sessionLifeTime = 0,
    timeWithoutUser = 0,
    agreementClasses = '',
    langP = '',
    requiredFields = '',
    varHashProduct = '';

document.cookie.split(/; */).forEach(function (cookieraw) {
    if (cookieraw.indexOf('mg_to_script') === 0) {
        var cookie = cookieraw.split('=');
        var name = cookie[0].substr(13);
        var value = decodeURIComponent(cookie[1]);
        window[name] = tryJsonParse(value.replace(/&nbsp;/g, ' '));
    }
});

// продление пхп сессии
if (sessionLifeTime > 0 && window.sessionUpdateActive !== true) {
    window.sessionUpdateActive = true;
    setInterval(function () {
        let dataObj = {
            actionerClass: 'Ajaxuser',
            action: 'updateSession'
        };

        let data = Object.keys(dataObj).map(function (k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(dataObj[k])
        }).join('&');

        const request = new XMLHttpRequest();

        request.open('POST', mgBaseDir + '/ajaxrequest', true);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

        request.addEventListener("readystatechange", () => {
            if (request.status < 200 && request.status >= 400) {
                console.log('Session update error!');
                console.log(request);
            }
        });

        request.send(data);

    }, (sessionLifeTime / 2 * 1000));
}


function tryJsonParse(str) {
    try {
        var res = JSON.parse(str);
        return res;
    } catch (e) {
        return str;
    }
}