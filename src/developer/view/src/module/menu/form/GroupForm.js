/**
 * Created by hejxi on 2017/1/19.
 */

import React from 'react'
import {Form, Icon, Input} from 'antd';
import Data from '../model/Data';

const FormItem = Form.Item;

let item, node = {};

export default React.createClass({
    getData() {
        return node;
    },
    contextTypes: {
        leftMenu: React.PropTypes.object
    },
    valueChange(type){
        return (e) =>{
            node[type] = e.target.value;
        };
    },
    render(){
        item = Data.findItem(this.props.itemKey);
        const {getFieldDecorator} = this.props.form;
        //判断添加的结点类型
        return <div>
            <FormItem>
                {getFieldDecorator('icon', {
                    rules: [{required: true, message: '请填写图标!'}],
                })(
                    <Input addonBefore={<Icon type={node.icon || 'inbox'}/>} onChange={this.valueChange('icon')}
                           placeholder="图标"/>
                )}
            </FormItem>
            <FormItem>
                {getFieldDecorator('to', {
                    rules: [{required: true, message: '请填写路径'}],
                })(
                    <Input placeholder="路径" onChange={this.valueChange('to')}/>
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
                {getFieldDecorator('desc', {
                    rules: [{}],
                })(
                    <Input type="textarea"
                           placeholder="介绍/说明"
                           autosize={{minRows: 2, maxRows: 6}}
                           onChange={this.valueChange('desc')}
                    />
                )}
            </FormItem>
        </div>;
    }
});
