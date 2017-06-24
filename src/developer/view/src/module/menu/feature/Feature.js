/**
 * Created by hejxi on 2017/1/20.
 */
import React from 'react'

import {Card, Button} from 'antd';
import {browserHistory} from 'react-router';

let item = {};
export default React.createClass({
    addFeature(){
        browserHistory.push(item.key + '/new');
    },
    settingFeature(index){
        browserHistory.push(item.key + '/' + index);
    },
    deleteFeature(index){
        item.feature.splice(index, 1);
        this.setState({});
    },
    moveUpFeature(index){
        const node = item.feature[index];
        item.feature.splice(index, 1);
        item.feature.splice(index - 1, 0, node);
        this.setState({});
    },
    moveDownFeature(index){
        const node = item.feature[index];
        item.feature.splice(index, 1);
        item.feature.splice(index + 1, 0, node);
        this.setState({});
    },
    render(){
        item = this.props.nodeItem;
        const features = item.feature || [];
        return <div className="feature-container">
            {features.map((feature, index) =>{
                const depends = feature.depend || [];
                return <Card key={index} title={<span>{feature.name}</span>} extra={
                    [
                        <Button.Group size="small" key="edit">
                            <Button title="设置" icon="setting" onClick={() => this.settingFeature(index)}/>
                            <Button title="删除" icon="minus-circle-o" onClick={() => this.deleteFeature(index)}/>
                        </Button.Group>,<span key="space">&nbsp;&nbsp;</span>,
                        <Button.Group size="small" key="move">
                            <Button title="向上移动" icon="arrow-up" disabled={index === 0} onClick={() => this.moveUpFeature(index)}/>
                            <Button title="向下移动" icon="arrow-down" disabled={index === features.length - 1}
                                    onClick={() => this.moveDownFeature(index)}/>
                        </Button.Group>
                    ]
                }>
                    {depends.map((af, i) =>{
                        return <p key={i}>{af.name}</p>
                    })}
                </Card>
            })}
            <div className="node-operation">
                <Button icon="plus" type="primary" onClick={this.addFeature}>添加功能</Button>
            </div>
        </div>
    }
});