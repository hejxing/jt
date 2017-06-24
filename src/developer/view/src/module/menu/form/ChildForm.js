/**
 * Created by hejxi on 2016/12/17.
 */
import React from 'react'
import {Form, Col, Radio, Button} from 'antd';
import Data from '../model/Data';
import {browserHistory} from 'react-router';
import lib from '../../../lib';

import GroupForm from './GroupForm';
import NodeForm from './NodeForm';
import RefForm from './RefForm';

const forms = {
    'group': GroupForm,
    'node': NodeForm,
    'ref': RefForm
};

let item = {}, node = {};

export default Form.create()(React.createClass({
    getInitialState(){
        return {};
    },
    contextTypes: {
        leftMenu: React.PropTypes.object
    },
    nodeTypeChange(e){
        node.type = e.target.value;
        this.setState({});
    },
    cancelHandler(){
        browserHistory.push(this.props.location.pathname.slice(0, -11));
    },
    addHandler(e){
        e.preventDefault();
        const d = this.refs.formParty.getData();
        d.type = node.type;
        this.props.form.validateFields((err) =>{
            if(!err){
                const date = new Date();
                d.key = (date.getTime() + date.getMilliseconds() / 1000).toString(36).replace('.', '');
                Data.addChild(item.key, lib.clone(d));
                this.context.leftMenu.state.expandedKeys.push(item.key);
                this.props.form.resetFields();
                this.context.leftMenu.setState({});
            }
        });
    },
    render(){
        const key = this.props.params.key || 'root';
        item = Data.curItem(key);
        const disable = {
            group: false,
            node: false,
            ref: false
        };

        if(Data.mixing){
            node.type = node.type || 'node';
        }else{
            if(item.level + 1 < Data.maxLevel){
                node.type = 'group';
                disable.node = true;
                disable.ref = true;
            }else{
                node.type = (node.type === 'group' || !node.type)? 'node': node.type;
                disable.group = true;
            }
        }
        const FormParty = forms[node.type];
        return <Col span="8">
            <Form className="edit-area" onSubmit={this.addHandler}>
                <Form.Item>
                    <Radio.Group value={node.type} onChange={this.nodeTypeChange}>
                        <Radio.Button value="group" disabled={disable.group}>页面分组</Radio.Button>
                        <Radio.Button value="node" disabled={disable.node}>页面</Radio.Button>
                        <Radio.Button value="ref" disabled={disable.ref}>引用</Radio.Button>
                    </Radio.Group>
                </Form.Item>
                <FormParty form={this.props.form} itemKey={key} ref="formParty"/>
                <Form.Item>
                    <Button className="login-form-button" onClick={this.cancelHandler} style={{marginRight: 10}}>
                        取消
                    </Button>
                    <Button type="primary" htmlType="submit" className="login-form-button">
                        添加
                    </Button>
                </Form.Item>
            </Form>
        </Col>;
    }
}));
