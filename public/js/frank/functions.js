function queryStringToObject(queryString = location.search.substr(1)) {

    let obj = {}
    let strs = queryString.split('&')

    for (let str of strs) {

        let pair = str.split('=')

        if (!pair[0]) continue

        let objRef = obj

        let paths = decodeURIComponent(pair[0]).split(/[\[\]]+/)

        let key = paths[0]

        if (paths.length > 1) {

            paths.pop()

            key = paths.pop()

            for (let path of paths) {
                if (!objRef[path]) {
                    objRef[path] = {}
                }
                objRef = objRef[path]
            }

        }

        objRef[key] = pair[1] ? decodeURIComponent(pair[1]) : ''

    }

    return obj
}

function objectToQueryString(obj) {

    let strs = []

    function cook(obj, prefix = '') {

        for (let key in obj) {

            let val = obj[key]
            key = prefix ? `${prefix}[${key}]` : key

            if (val instanceof Object) {
                cook(val, key)
            } else {
                key = encodeURIComponent(key)
                val = encodeURIComponent(val)
                strs.push(`${key}=${val}`)
            }
        }

    }

    cook(obj)

    return strs.join('&')
}

function objectFilte(obj, keys = [], except = true) {

    keys = new Set(keys)

    let result = {}

    for (let k in obj) {
        if (except) {
            if (keys.has(k)) continue
        } else {
            if (!keys.has(k)) continue
        }
        result[k] = obj[k]
    }

    return result
}

function rows2object(rows, keyFields, valueField = null) {

    let dataObject = {}

    for (let row of rows) {
        let key = (keyFields instanceof Array) ? keyFields.map(k => row[k]).join(':') : row[keyFields]
        dataObject[key] = valueField ? row[valueField] : row
    }

    return dataObject
}

/**
 * @param params todo 自动提示
 */
function bindDelayEvents(...params) {

    if (params[3] instanceof Function) {
        var [capturEles, eTypes, eles, callback, delay = 76] = params
    } else if (params[2] instanceof Function) {
        var [eles, eTypes, callback, delay = 76] = params
    } else {
        throw new Error('Invalid callback')
    }

    let stid = 0

    function func(...args) {
        clearTimeout(stid)
        stid = setTimeout(callback.bind(this, ...args), delay)
    }

    // 既可以是数组，也可以是空格分隔的字符串
    (eTypes instanceof Array) || (eTypes = eTypes.split(/\s+/));

    if (capturEles) {

        if (capturEles instanceof Element) {
            capturEles = [capturEles]
        } else {
            capturEles = document.querySelectorAll(capturEles)
        }

        for (let capturEle of capturEles) {

            for (let eType of eTypes) {
                capturEle.addEventListener(eType, (e, ...args) => {
                    // e.target.matches(eles)
                    if (new Set(capturEle.querySelectorAll(eles)).has(e.target)) {
                        // 修改属性的私有、只读等限制
                        Object.defineProperty(e, 'currentTarget', {writable: true})
                        // Object.getOwnPropertyDescriptor(e, 'currentTarget')
                        e.currentTarget = e.target
                        // e.delegateTarget = capturEle
                        func.call(e.target, e, ...args)
                        // event.stopPropagation();
                        // event.cancelBubble = bool;
                    }
                }, {capture: true});
            }

        }

    } else {

        (eles instanceof Element) && (eles = [eles]);
        (eles instanceof Array) || (eles = eles.split(','));

        for (let eType of eTypes) {
            for (let ele of eles) {
                (ele instanceof Element) || (ele = document.querySelector(ele));
                ele && ele.addEventListener(eType, func);
            }
        }

    }
}

/**
 * 选中文本
 */
function selectText(ele) {
    if (document.selection) {
        var range = document.body.createTextRange();
        range.moveToElementText(ele);
        range.select();
    } else if (window.getSelection) {
        window.getSelection().empty();
        var range = document.createRange();
        range.selectNodeContents(ele);
        window.getSelection().addRange(range);
    }
}

function getUrlFileName(url) {
    let ms = url.match(/([^/]+\.\w+)$/)
    let file = ms ? ms[1] : url
    return file
}