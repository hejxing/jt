/**
 * Created by hejxi on 2016/12/12.
 */
import Ajax from '../../../ajax';

export default {
    maxLevel: 2,
    mixing: false,
    curKey: null,
    item: [{
        key: 'root',
        icon: 'bars',
        name: '页面列表',
        type: 'group',
        item: [{
            key: '1',
            icon: 'loading',
            name: '...'
        }]
    }],
    apiList: [],

    getApiList(){
        if(this.apiList.length){
            return new Promise((resolve)=>{
                resolve();
            });
        }
        return Ajax.get('../menu/api/list').success((data, xhr, q)=>{
            this.apiList = data;
        });
    },

    load(fn){
        Ajax.get('../menu/list.json').success((d) =>{
            if(d.maxLevel){
                this.maxLevel = d.maxLevel;
            }
            this.mixing = !!d.mixing;
            this.item[0].item = d.list || [];
            this.feature = d.feature || {};
            fn(d);
        });
    },
    save(fn){
        Ajax.put('../menu/list.json', {data: {data: {list: this.item[0].item, feature: this.feature}}, arrayIndexIgnore: true}).success((d) =>{
            fn(d);
        });
    },
    addChild(parentKey, node){
        const item = this.findItem(parentKey);
        if(!item.item){
            item.item = [];
        }
        item.item.push(node);
    },
    remove(item){
        const parent = this.findItem(item.parentKey);
        parent.item.splice(item.index, 1);
        if(!parent.item.length){
            delete parent.item;
        }
    },
    curItem(key){
        const item = this.findItem(key);
        if(key){
            this.curKey = key;
        }
        return item;
    },
    findItem(key){
        if(key === null){
            return this;
        }
        const find = function(list, parentKey, level){
            for(let index in list){
                if(list.hasOwnProperty(index)){
                    const item = list[index];
                    if(item.key === key){
                        item.parentKey = parentKey;
                        item.index = index;
                        item.level = level;
                        return item;
                    }
                    if(item.item){
                        let fd = find(item.item, item.key, level + 1);
                        if(fd){
                            return fd;
                        }
                    }
                }
            }
        };
        return find(this.item, null, 0) || {};
    },
    move(item, to, gap){
        const parentItem = this.findItem(item.parentKey);
        parentItem.item.splice(item.index, 1);

        if(gap){
            const toParentItem = this.findItem(to.parentKey);
            toParentItem.item.splice(to.index, 0, item);
        }else{
            if(!to.item){
                to.item = [];
            }
            to.item.push(item);//放在最后面
        }
    },
    deep(item){
        let maxDeep = 0;
        const find = function(item, deep){
            const list = item.item || [];
            for(let index in list){
                if(list.hasOwnProperty(index)){
                    find(list[index], deep + 1);
                }
            }
            maxDeep = Math.max(maxDeep, deep);
        };
        find(item, 1);
        return maxDeep;
    }
};