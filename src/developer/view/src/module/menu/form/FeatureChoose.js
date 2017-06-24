/**
 * Created by hejxi on 2016/12/17.
 */
import React from 'react'
import {Form, Col, Cascader, Input, Icon, Button, Tree} from 'antd';
import Data from '../model/Data';
import {browserHistory} from 'react-router';

let item = {}, feature, featureIndex;
export default Form.create()(React.createClass({
    getInitialState(){
        return {
            options: [{
                value: '0',
                label: '正在加载，请稍候...'
            }],
            featureName: '',
            selectedFeature: {
                name: '',
                code: '',
                desc: ''
            },
            index: 0
        };
    },
    loadData(){
        return Data.getApiList().then(() =>{
            this.setState({
                options: Data.apiList
            });
        });
    },
    format(isMaster){
        return (label, nodes) => {
            const node = nodes.length? nodes[nodes.length - 1]: {value: '', name: ''};
            if(isMaster){
                ['name', 'code', 'desc', 'notice'].forEach((name)=>{
                    this.state.selectedFeature[name] = node[name];
                });
            }
            return label.join(' / ');
        };
    },
    addDependent(){
        feature.depend.push({});
        this.setState({});
    },
    removeDependent(index){
        feature.depend.splice(index, 1);
        this.setState({});
    },
    masterFeatureChange(value, nodes){
        if(nodes.length){
            const node = nodes[nodes.length - 1];
            feature.value = value;
            node.code = node.value;
            ['name', 'code', 'desc', 'notice'].forEach((name)=>{
                if(!feature[name] || feature[name] === this.state.selectedFeature[name]){
                    feature[name] = node[name];
                }
            });
        }else{
            feature.value = [];
        }
        this.setState({});
    },
    dependFeatureChange(index){
        return (value, nodes) =>{
            const node = nodes.length? nodes[nodes.length - 1]: {
                name: ''
            };
            feature.depend[index] = {
                value: value.length? value: [],
                name: node.name
            };
            this.setState({});
        }
    },
    featureChange(name){
        return e =>{
            feature[name] = e.target.value;
            this.setState({});
        }
    },
    resetFeature(name){
        return () =>{
            feature[name] = this.state.selectedFeature[name];
            this.setState({});
        }
    },
    saveFeature(){
        if(!item.feature){
            item.feature = [];
        }
        item.feature[featureIndex] = feature;
        browserHistory.push(this.props.location.pathname.replace(/(.*)\/.*/, "$1"));
    },
    componentWillMount(){
        this.loadData();
        feature = null;
    },
    render(){
        const key = this.props.params.key || 'root';
        const pi = this.props.params.index;

        item = Data.curItem(key);
        if(pi === 'new'){
            featureIndex = (item.feature && item.feature.length) || 0;
        }else{
            featureIndex = parseInt(pi, 10);
        }

        if(!feature || !feature.name){
            feature = item.feature && item.feature[featureIndex] || {
                name: '',
                value: ''
            };
        }

        if(!feature.depend){
            feature.depend = [];
        }

        const dependList = feature.depend || [];
        const dependentItems = dependList.map((item, index) =>{
            return (
                <Col span="20" key={index}>
                    <Input.Group compact style={{paddingLeft: '30px'}}>
                        <Cascader style={{width: '100%', marginLeft: '-30px'}} popupClassName="api-select" options={this.state.options}
                                  value={item.value} showSearch={true} displayRender={this.format(false)} notFoundContent="怎么也找不到呀"
                                  onChange={this.dependFeatureChange(index)}
                                  placeholder="输入关键字 快速定位"/>
                        <Button
                            style={{marginLeft: '-1px'}}
                            icon="minus-circle"
                            onClick={() => this.removeDependent(index)}
                        />
                    </Input.Group>
                </Col>
            );
        });

        return <Col span="18" className="choose-feature">
            <h2 className="page-title">为 {item.name} 页面添加功能</h2>
            <h3 className="page-title">选择主功能</h3>
            <Cascader className="master-feature" prefix={<Icon type="api" style={{fontSize: 13}}/>} style={{width: '100%'}}
                      popupClassName="api-select" options={this.state.options}
                      value={feature.value} showSearch={true} displayRender={this.format(true)} notFoundContent="怎么也找不到呀"
                      onChange={this.masterFeatureChange}
                      placeholder="输入关键字 快速定位"/>
            <Input value={feature.code} onChange={this.featureChange('code')} placeholder="功能标识" style={{width: '60%', marginBottom: '5px'}}
                   prefix={<Icon type="key" style={{fontSize: 13}}/>}
                   suffix={<Icon
                       type="reload"
                       onClick={this.resetFeature('code')}
                   />}
                />
            <Input value={feature.name} onChange={this.featureChange('name')} placeholder="功能名称" style={{width: '60%',marginBottom: '5px'}}
                   prefix={<Icon type="tag-o" style={{fontSize: 13}}/>}
                   suffix={<Icon
                       type="reload"
                       onClick={this.resetFeature('name')}
                   />}/>
            <Input value={feature.notice}
                   onChange={this.featureChange('notice')}
                   prefix={<Icon type="exclamation-circle-o" style={{fontSize: 13}}/>}
                   placeholder="注意事项"
                   style={{width: '100%',marginBottom: '5px'}}
                   suffix={<Icon
                       type="reload"
                       onClick={this.resetFeature('notice')}
                   />}/>
            <Input value={feature.desc}
                   onChange={this.featureChange('desc')}
                   prefix={<Icon type="info-circle-o" style={{fontSize: 13}}/>}
                   placeholder="功能说明/介绍"
                   style={{width: '100%'}}
                   suffix={<Icon
                       type="reload"
                       onClick={this.resetFeature('desc')}
                   />}/>
            <Col span="24" className="choose-feature-dependent">
                <h3 className="page-title">选择依赖的其它功能</h3>
                <div className="dependent-box">
                    {dependentItems}
                    <Col span="24">
                        <Button onClick={this.addDependent}>
                            <Icon type="plus"/> 添加依赖项
                        </Button>
                    </Col>
                </div>
            </Col>
            <div style={{paddingTop: '30px'}}>
                <Button icon="check" type="primary" onClick={this.saveFeature}>确定</Button>
            </div>
        </Col>;
    }
}));
