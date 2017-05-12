/**
 * Created by ax on 2016/11/30.
 */
import React from 'react'
import Data from '../model/Data';

import {Radio, Button} from 'antd';
import {Modal, Icon, message} from 'antd';
import {browserHistory} from 'react-router';

import Feature from '../feature/Feature';

let item = {}, nodeInstance;

export default React.createClass({
    onChangeType(d){
        if(d.target.value === 'group' && item.level >= Data.maxLevel){
            message.error('此结点已达到允许的最大层级(' + Data.maxLevel + '级)，不允许设为此类型');
            return;
        }
        if(!Data.mixing && d.target.value !== 'group' && item.level < Data.maxLevel){
            message.error('选择的结点类型只允许出现在末级(第' + Data.maxLevel + '级)');
            return;
        }
        if(item.type === d.target.value){
            return;
        }
        const self = this;
        Modal.confirm({
            title: '确定改变结点类型',
            content: '该操作将改变本结点的类型，修改后将导致原结点信息丢失，请再次确认！',
            okText: '确认修改',
            cancelText: '取消',
            onOk(){
                item.type = d.target.value;
                self.setState({});
            }
        });
    },
    contextTypes: {
        leftMenu: React.PropTypes.object
    },
    modifyItem(e){
        browserHistory.push(item.key + '/setting');
        e.preventDefault();
    },
    removeItem(e){
        e.preventDefault();
        if(item.item && item.item.length){
            message.error('此结点下有子结占，不能删除! 请先清理子结点后再试.');
        }else{
            const parent = Data.findItem(item.parentKey);
            let nextItem = parent;

            if(item.index > 0){
                nextItem = parent.item[item.index - 1];
            }else if(parent.item.length >= 2){
                nextItem = parent.item[1];
            }
            Data.remove(item);
            browserHistory.push(nextItem.key);
        }
    },
    valueChange(key){
        return e =>{
            item[key] = e.target.value;
            this.setState({});
        };
    },
    addChild(){
        //先打开一个子结点表格
        browserHistory.push(item.key + '/child_form');
    },

    render(){
        nodeInstance = this;
        const key = this.props.params.key || 'root';
        item = Data.curItem(key);
        //加载数据
        return <div className="node-item">
            <div className="editable">
                <div className="title">
                    <Icon type={item.icon}/>
                    <span>{item.name}</span>
                    <a href="#" className="edit-btn" size="small" onClick={this.modifyItem}>
                        <Icon type="edit"/>修改
                    </a>
                    <a href="#" className="edit-btn" size="small" onClick={this.removeItem}>
                        <Icon type="close"/>删除
                    </a>
                </div>
                {item.type === 'group'? null:
                    <div><span className="response">{item.to}</span></div>
                }
                <div className="desc">{item.desc}</div>
            </div>
            <div>
                <Radio.Group onChange={this.onChangeType} value={item.type} size="small">
                    <Radio.Button value="group">页面分组</Radio.Button>
                    <Radio.Button value="node">页面</Radio.Button>
                    <Radio.Button value="ref">引用</Radio.Button>
                </Radio.Group>
            </div>
            {item.level < Data.maxLevel?
                <div className="node-operation">
                    <Button icon="plus" onClick={this.addChild}>添加子节点</Button>
                </div>: null
            }
            {item.type === 'group'? null:
                <Feature nodeItem={item}/>
            }
        </div>;
    }
});