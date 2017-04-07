/**
 * Created by ax on 2016/11/30.
 */
import React from "react";
import Data from "./model/Data";

import {Button, Col, Icon, Input, message, Modal, Radio} from "antd";
import {browserHistory} from "react-router";

let item = {}, nodeInstance;

const action = {
    addChild(){
        //先打开一个子结点表格
        browserHistory.push(item.key + '/item');
    }
};

const createNodeDetail = (item) =>{
    let nodeDetail;
    switch(item.type){
        case 'group':
            nodeDetail = <div className="node-operation">
                <Button icon="plus" onClick={action.addChild}>添加子节点</Button>
            </div>;
            break;
        case 'node':
            nodeDetail = <div>Node</div>;
            break;
        case 'ref':
            nodeDetail = <div>Ref</div>;
            break;
        default:
            nodeDetail = <div/>;
            break;
    }
    return nodeDetail;
};

const Node = React.createClass({
    getInitialState(){
        return {
            editable: false
        }
    },
    onChangeType(d){
        if(d.target.value === 'group' && item.level >= Data.maxLevel){
            message.error('此结点已达到允许的最大层级(' + Data.maxLevel + ')，不允许设为此类型');
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
        menuList: React.PropTypes.object
    },
    modifyName(e){
        const state = this.state.editable;
        this.setState({
            editable: !state
        });
        e.preventDefault();
    },
    componentDidUpdate(){
        if(this.context.menuList){
            this.context.menuList.setState({});
        }
    },
    valueChange(key){
        return e =>{
            item[key] = e.target.value;
            this.setState({});
        };
    },
    render(){
        nodeInstance = this;
        const key = this.props.params.key || 'root';
        item = Data.findItem(key);
        if(this.context.menuList){
            this.context.menuList.state.selectedKeys = [key];
            this.context.menuList.state.expandedKeys.push(item.parentKey);
        }
        //加载数据
        return (
            <div className="node-item">
                <div className="title">
                    {item.key === 'root'? <div className="node-item">
                        <div>欢迎使用功能菜单编辑器</div>
                        <div className="desc">只供开发人员使用，只允许在开发环境下使用!</div>
                    </div>: (this.state.editable?
                        <div>
                            <Input.Group size="small">
                                <Col span="2">
                                    <Input value={item.icon} onChange={this.valueChange('icon')} placeholder="图标"/>
                                </Col>
                                <Col span="6">
                                    <Input value={item.name} onChange={this.valueChange('name')} placeholder="名称"/>
                                </Col>
                                <Col span="2">
                                    <a href="#" className="edit-btn" size="small" onClick={this.modifyName}>
                                        <Icon type="check"/>确认
                                    </a>
                                </Col>
                            </Input.Group>
                        </div>:
                        <div>
                            <Icon type={item.icon}/>
                            <span>{item.name}</span>
                            <a href="#" className="edit-btn" size="small" onClick={this.modifyName}>
                                <Icon type="edit"/>修改
                            </a>
                        </div>)
                    }
                </div>
                {this.state.editable?
                    <div>
                        <Input type="textarea"
                               onChange={this.valueChange('desc')}
                               placeholder="简介" value={item.desc}
                               autosize={{minRows: 2, maxRows: 6}}
                               style={{width: '24em'}}
                        />
                    </div>
                    :
                    <div className="desc">{item.desc}</div>
                }
                {item.key === 'root'?
                    null: <Radio.Group onChange={this.onChangeType} value={item.type} size="small">
                        <Radio.Button value="group">功能分组</Radio.Button>
                        <Radio.Button value="node">功能</Radio.Button>
                        <Radio.Button value="ref">引用</Radio.Button>
                    </Radio.Group>}
                {createNodeDetail(item)}
            </div>
        );
    }
});

export default Node;