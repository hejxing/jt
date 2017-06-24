require('../style/base.less');

import React from 'react'
import {render} from 'react-dom'
import {Row, Col, Menu, Icon} from 'antd'

const Header = React.createClass({
    getInitialState() {
        let ps = location.pathname.split('/');
        return {
            current: ps[ps.length - 2]
        };
    },
    render(){
        return (
            <Row className="header-bar">
                <Col className="app-name" span={4}>开发小助手</Col>
                <Col span={20}>
                    <div className="navigation">
                        <Menu onClick={this.handleClick}
                              selectedKeys={[this.state.current]}
                              mode="horizontal"
                        >
                            <Menu.Item key="menu">
                                <Icon type="appstore-o"/>
                                <a href="../menu/" rel="noopener noreferrer">功能菜单</a>
                            </Menu.Item>
                            <Menu.Item key="docs">
                                <Icon type="book"/>
                                <a href="../docs/" rel="noopener noreferrer">API文档</a>
                            </Menu.Item>
                        </Menu>
                    </div>
                </Col>
            </Row>
        );
    }
});

render(<Header/>, document.querySelector('header'));