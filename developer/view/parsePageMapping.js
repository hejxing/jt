const pageMapping = require('./config/pageMapping');
const fs = require('fs');

const parsePageMapping = function(config){
    let page = {chunks: {comm: {}}};
    let entry = {};
    if(typeof config === 'string'){
        page.name = config + '/index';
        page.chunks[config] = {};
        entry.name = config;
        entry.js = config + '/route_rule.js'
    }else{
        page.name = config.name;
        if(config.chunks){
            page.chunks = config.chunks;
        }else if(config.entry){
            if(typeof config.entry === 'string'){
                page.chunks[config.entry] = {};
                entry.name = config.entry;
                entry.js = config.entry + '/route_rule.js'
            }else{
                page.chunks[config.entry.name] = {};
                entry = config.entry;
            }
        }
        if(config.tpl){
            page.tpl = 'src/' + config.tpl;
        }
    }

    if(!page.tpl){
        page.tpl = 'src/layout.tpl';
    }

    return {page, entry};
};

//React-router hack
// let jsFile = './node_modules/react-router/lib/createTransitionManager.js';
// let source = fs.readFileSync(jsFile).toString();
// let regExp = /( *).*Location "%s" did not match any routes.*/;
// if(regExp.test(source)){
//     console.info('Hack React-router');
//     source = source.replace(regExp, '$1window.location.href = location.pathname + location.search + location.hash;');
//     fs.writeFileSync(jsFile, source);
// }

module.exports = function(config){
    let parsed = {
        page: [],
        entry: {}
    };
    for(let i = 0, l = pageMapping.length; i < l; i++){
        let res = parsePageMapping(pageMapping[i]);
        let js = typeof res.entry.js === 'string'? [res.entry.js]: res.entry.js;
        js.map((s, i) =>{
            if(s && s.indexOf('/') !== 0){
                js[i] = config.path.src + '/module/' + s;
            }
        });
        parsed.entry[res.entry.name] = js;
        if(!js.length){
            res.page.chunks = {};
        }
        parsed.page.push(res.page);
    }
    //parsed.entry.peace = [config.path.src + '/component/Peace.js'];
    return parsed;
};