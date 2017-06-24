import React from 'react'
import {render} from 'react-dom'

import {Router, Route, IndexRoute, Redirect, browserHistory} from 'react-router'

import Home from './Home'
import RootItem from './RootItem'
import ItemDetail from './item/ItemDetail'
import ItemSetting from './item/ItemSetting'
import ChildForm from './form/ChildForm'
import FeatureChoose from './form/FeatureChoose'

require('../header');

render((
    <Router history={browserHistory}>
        <Route path="/" component={Home}>
            <IndexRoute component={RootItem}/>
            <Redirect from="/root" to="/"/>
            <Route path=":key" component={ItemDetail}/>
            <Route path=":key/setting" component={ItemSetting}/>
            <Route path=":key/child_form" component={ChildForm}/>
            <Route path=":key/:index" component={FeatureChoose}/>
        </Route>
    </Router>
), document.getElementById('app'));