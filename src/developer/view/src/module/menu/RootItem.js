/**
 * Created by ax on 2016/11/30.
 */
import React from 'react'
import Data from './model/Data';

import {Button} from 'antd';
import {browserHistory} from 'react-router';


const Node = React.createClass({
    addChild(){
        browserHistory.push('root/child_form');
    },
    render(){
        const item = Data.curItem('root');
        //加载数据
        return (
            <div className="node-item">
                <div className="title">欢迎使用页面菜单编辑器</div>
                <div className="desc">只供开发人员使用，只允许在开发环境下使用!</div>
                <div className="desc">{item.desc}</div>
                <div className="node-operation">
                    <Button icon="plus" onClick={this.addChild}>添加子节点</Button>
                </div>
            </div>
        );
    }
});

export default Node;