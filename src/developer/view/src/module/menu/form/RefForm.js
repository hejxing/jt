/**
 * Created by hejxi on 2017/1/19.
 */

import React from 'react'
import {Form, Icon, Input, Button, Radio, Col} from 'antd';
import Data from '../model/Data';
import {browserHistory} from 'react-router';

const FormItem = Form.Item;

let item, node = {};

export default Form.create()(React.createClass({
    getInitialState(){
        return {};
    },
    addGroupHandler(e) {
        e.preventDefault();
        this.props.form.validateFields((err) =>{
            if(!err){
                const date = new Date();
                node.key = (date.getTime() + date.getMilliseconds() / 1000).toString(36).replace('.', '');
                Data.addChild(item.key, node);
                this.setState({});
            }
        });
    },
    addNodeHandler(e){
        e.preventDefault();
    },
    componentDidUpdate(){
        if(this.context.leftMenu){
            this.context.leftMenu.setState({});
        }
    },
    contextTypes: {
        leftMenu: React.PropTypes.object
    },
    valueChange(type){
        return (e) =>{
            node[type] = e.target.value;
        };
    },
    nodeTypeChange(e){
        node.type = e.target.value;
        this.setState({});
    },
    cancelHandler(){
        let uri = this.props.location.pathname.slice(0, -11);
        if(uri === 'root'){
            uri = '/';
        }
        browserHistory.push(uri);
    },
    render(){
        const key = this.props.params.key || 'root';
        item = Data.curItem(key);
        const {getFieldDecorator} = this.props.form;
        //判断添加的结点类型
        if(item.level + 1 < Data.maxLevel){
            node.type = 'group';
            return <Col span="8">
                <GroupForm />
            </Col>
        }else{
            node.type = node.type || 'node';
            if(node.type === 'group'){
                node.type = 'node';
            }
            return <Col span="8">
                <Form className="edit-area" onSubmit={this.addNodeHandler}>
                    <FormItem>
                        <Radio.Group value={node.type} onChange={this.nodeTypeChange}>
                            <Radio.Button value="group" disabled={item.level + 1 >= Data.maxLevel}>页面分组</Radio.Button>
                            <Radio.Button value="node">页面</Radio.Button>
                            <Radio.Button value="ref">引用</Radio.Button>
                        </Radio.Group>
                    </FormItem>
                    <FormItem>
                        {getFieldDecorator('icon', {
                            rules: [{required: true, message: '请填写图标!'}],
                        })(
                            <Input addonBefore={<Icon type={node.icon || 'inbox'}/>} onChange={this.valueChange('icon')}
                                   placeholder="图标"/>
                        )}
                    </FormItem>
                    <FormItem>
                        {getFieldDecorator('name', {
                            rules: [{required: true, message: '请填写名称!'}],
                        })(
                            <Input placeholder="名称" onChange={this.valueChange('name')}/>
                        )}
                    </FormItem>

                    <FormItem>
                        <Input type="textarea"
                               placeholder="介绍/说明"
                               autosize={{minRows: 2, maxRows: 6}}
                               onChange={this.valueChange('desc')}
                        />
                    </FormItem>
                    <FormItem>
                        <Button className="login-form-button" onClick={this.cancelHandler} style={{marginRight: 10}}>
                            取消
                        </Button>
                        <Button type="primary" htmlType="submit" className="login-form-button">
                            添加
                        </Button>
                    </FormItem>
                </Form>
            </Col>
        }
    }
}));
