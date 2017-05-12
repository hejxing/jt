import React from 'react'
import {Tree, Input, Icon, message} from 'antd';
import {browserHistory} from 'react-router';
const TreeNode = Tree.TreeNode;
const Search = Input.Search;

import Data from './model/Data';

class LeftMenu extends React.Component {
    constructor(props){
        super(props);
        this.state = {
            searchValue: '',
            expandedKeys: ['root'],
            clickExpand: false,
            selectedKeys: ['root']
        };
    };

    onExpand = (expandedKeys, state) =>{
        if(!state.expanded){
            state.node.props.children.map((item) =>{
                let index = expandedKeys.indexOf(item.key);
                if(index > -1){
                    delete expandedKeys[index];
                }
            });
        }
        this.setState({
            expandedKeys: expandedKeys,
            clickExpand: true
        });
    };

    componentDidMount(){
        Data.load((d) =>{
            this.state.selectedKeys = [Data.curKey];
            this.state.expandedKeys.push(Data.curKey);
            this.props.home.fresh();
        });
    };

    onChange = (e) =>{
        this.setState({
            searchValue: e.target.value,
            expandedKeys: ['root'],
            clickExpand: false
        });
    };

    onDragStart = (info) =>{
        //fix bug
        info.node.props.root.getRawExpandedKeys();
    };

    onDrop = (info) =>{
        const dropKey = info.node.props.eventKey;
        const dragKey = info.dragNode.props.eventKey;
        // const dragNodesKeys = info.dragNodesKeys;
        const dropItem = Data.findItem(dropKey);
        if(!info.dropToGap && dropItem.type !== 'group'){
            message.error('该结点类型，不允许有子结点');
            return;
        }

        if(info.dropToGap && dropItem.parentKey === null){
            message.error('不允许存在多个根结点');
            return;
        }

        const dragItem = Data.findItem(dragKey);
        const deep = Data.deep(dragItem);

        if(dropItem.level + deep > Data.maxLevel + (info.dropToGap? 1: 0)){
            message.error('移动后将超过最大层级(' + Data.maxLevel + ')，操作无效');
            return;
        }

        if(dragItem.type === 'node' && dropItem.level < Data.maxLevel + (info.dropToGap? 1: 0)){
            message.error('该结点只能位于最末级，操作无效');
            return;
        }

        Data.move(dragItem, dropItem, info.dropToGap);
        this.setState({});
    };

    onSelect = (selectedKeys, e) =>{
        const key = selectedKeys.pop() || this.state.selectedKeys.pop();
        if(key){
            this.state.selectedKeys = [key];
            this.state.expandedKeys = [key];
            browserHistory.push(key === 'root'? '/': '/' + key);
        }
    };

    render(){

        const {searchValue, expandedKeys} = this.state;
        let parentNode;
        const loop = (data) => data.map((item) =>{
            const index = item.name.search(searchValue);
            let title;
            if(searchValue && index > -1){
                let beforeStr = item.name.substr(0, index);
                let afterStr = item.name.substr(index + searchValue.length);
                title =
                    <span><Icon type={item.icon}/> {beforeStr}<span
                        className="search-match">{searchValue}</span>{afterStr}</span>;
                //将父结点加入到展开的列表中
                if(parentNode && !this.state.clickExpand){
                    expandedKeys.push(parentNode.key);
                }
            }else{
                title = <span><Icon type={item.icon}/> {item.name}</span>;
            }
            if(item.item){
                parentNode = item;
                return (
                    <TreeNode key={item.key} title={title}>
                        {loop(item.item)}
                    </TreeNode>
                );
            }
            return <TreeNode key={item.key} title={title}/>;
        });
        const treeNodes = loop(Data.item);

        return (
            <div className="menu-list">
                <Search
                    className="search-input"
                    placeholder="过滤"
                    onChange={this.onChange}
                />
                <Tree
                    className="menu-tree"
                    onExpand={this.onExpand}
                    expandedKeys={expandedKeys}
                    selectedKeys={this.state.selectedKeys}
                    draggable
                    onDragStart={this.onDragStart}
                    onDrop={this.onDrop}
                    onSelect={this.onSelect}
                >
                    {treeNodes}
                </Tree>
            </div>
        );
    }
}

export default LeftMenu;