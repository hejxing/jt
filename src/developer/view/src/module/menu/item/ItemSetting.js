/**
 * Created by ax on 2016/11/30.
 */
import React from 'react'
import Data from '../model/Data';

import {Radio, Button, Input} from 'antd';
import {Cascader, Modal, Icon, message} from 'antd';
import {browserHistory} from 'react-router';
import Feature from '../feature/Feature';

let item = {}, nodeInstance;

export default React.createClass({
    getInitialState(){
        return {
            options: [{
                value: '0',
                label: '正在加载，请稍候...'
            }],
            featureName: item.name,
            selectedFeature: {
                name: '',
                to: ''
            },
            index: 0,
            dependentList: []
        };
    },
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
        e.preventDefault();
        browserHistory.push(this.props.location.pathname.slice(0, -8));
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
    loadData(){
        return Data.getApiList().then(() =>{
            this.setState({
                options: Data.apiList
            });
        });
    },
    parseLink(v){
        return v.replace(/[^:]*:([^\*]+).*/, '$1');
    },
    format(label, nodes){
        const node = nodes.length?nodes[nodes.length - 1]:{value: '', name: ''};
        let v = label.join(' / ') + (node.value?' [' + node.value + ']':'');
        this.state.selectedFeature = {
            name: node.name,
            to: this.parseLink(node.value)
        };
        return v.replace('*path]', ']');
    },
    masterFeatureChange(value, nodes){
        if(nodes.length){
            const node = nodes[nodes.length - 1];
            if(!item.name || item.name === this.state.selectedFeature.name){
                item.name = node.name;
            }
            if(!item.to || item.to === this.state.selectedFeature.to){
                item.to = this.parseLink(node.value);
            }
            item.api = value;
        }else{
            item.api = [];
        }
        this.setState({});
    },
    resetFeature(type){
        return () => {
            item[type] = this.state.selectedFeature[type];
            this.setState({});
        };
    },
    componentWillMount(){
        this.loadData();
        nodeInstance = this;
    },
    render(){
        item = Data.curItem(this.props.params.key || 'root');
        //加载数据
        return <div className="node-item">
            <div className="title choose-feature">
                <Cascader className="master-feature" prefix={<Icon type="api" style={{fontSize: 13}}/>} style={{width: '42em'}}
                          popupClassName="api-select" options={this.state.options}
                          value={item.api} showSearch={true} displayRender={this.format} notFoundContent="怎么也找不到呀"
                          onChange={this.masterFeatureChange}
                          placeholder="输入关键字 快速定位"/>
                <a href="#" style={{marginLeft:'5px'}} className="edit-btn" size="small" onClick={this.modifyItem}>
                    <Icon type="check"/>确认
                </a>
            </div>
            <div>
                <Input.Group compact>
                    <Input style={{width: '10em'}} value={item.icon} onChange={this.valueChange('icon')} placeholder="图标" prefix={<Icon type={item.icon || 'inbox'}/>}/>
                    <Input style={{width: '32em'}} value={item.name} onChange={this.valueChange('name')} placeholder="名称"
                           prefix={<Icon type="tag-o" style={{fontSize: 13}}/>}
                           suffix={<Icon
                               type="reload"
                               onClick={this.resetFeature('name')}
                           />}/>
                </Input.Group>
            </div>
            <div>
                <Input.Group compact>
                    <Input style={{width: '36em'}} value={item.to} onChange={this.valueChange('to')} placeholder="链接地址"
                           prefix={<Icon type="link" style={{fontSize: 13}}/>}
                           suffix={<Icon
                               type="reload"
                               onClick={this.resetFeature('to')}
                           />}/>
                </Input.Group>
            </div>
            <div>
                <Input type="textarea"
                       onChange={this.valueChange('desc')}
                       placeholder="简介" value={item.desc}
                       autosize={{minRows: 2, maxRows: 6}}
                       style={{width: '42em'}}
                />
            </div>
            <div>
                <Radio.Group onChange={this.onChangeType} value={item.type} size="small">
                    <Radio.Button value="group">页面分组</Radio.Button>
                    <Radio.Button value="node">页面</Radio.Button>
                    <Radio.Button value="ref">引用</Radio.Button>
                </Radio.Group>
            </div>
            {item.level >= Data.maxLevel? null:
                <div className="node-operation">
                    <Button icon="plus" onClick={this.addChild}>添加子节点</Button>
                </div>
            }
            {item.type === 'group'? null:
                <Feature nodeItem={item}/>
            }
        </div>;
    }
});