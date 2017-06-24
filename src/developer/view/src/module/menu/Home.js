import React from 'react'
import {Button, message} from 'antd';
import Data from './model/Data';
import LeftMenu from'./LeftMenu';

export default React.createClass({
    contextTypes: {
        router: React.PropTypes.object.isRequired
    },
    reset(){
        this.refs.leftMenu.componentDidMount();
    },
    saveToServer(){
        Data.save(() =>{
            message.info('保存成功!');
        });
    },
    childContextTypes: {
        leftMenu: React.PropTypes.object
    },
    getChildContext: function(){
        return {leftMenu: this.refs.leftMenu};
    },
    fresh(){
        this.context.router.replace(this.props.location.pathname);
    },
    render() {
        return (
            <div className="body-box">
                <LeftMenu home={this} ref="leftMenu"/>
                <div className="node-container">
                    {this.props.children}
                </div>
                <div className="toolbar">
                    <Button icon="reload" size="small" onClick={this.reset} style={{marginRight: 10}}>重置</Button>
                    <Button icon="cloud-upload-o" type="primary" size="small" onClick={this.saveToServer}>保存</Button>
                </div>
            </div>
        );
    }
});