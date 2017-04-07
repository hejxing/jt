import React from "react";
import {render} from "react-dom";

import {browserHistory, IndexRoute, Route, Router} from "react-router";

import Home from "./Home";
import Node from "./Node";
import NodeForm from "./NodeForm";

require('../header');

render((
    <Router history={browserHistory}>
        <Route path="/" component={Home}>
            <IndexRoute component={Node}/>
            <Route path=":key" component={Node}/>
            <Route path=":key/item" component={NodeForm}/>
        </Route>
    </Router>
), document.getElementById('app'));