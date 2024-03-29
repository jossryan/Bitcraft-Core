import Navbar   from './core/Navbar';
import Search   from './core/Search';
import Sidebar  from './core/Sidebar';
import State    from './core/State';

// V is the global server object that is defined in a template from our backend.
let V = V || {};

// Bind our server object with our app state
let state   = new State(V);

// Bind elements to our application state
state.addElements({
    navbar : {
        name : 'navbar',
        ref : new Navbar(state.getServerContext().navbar)
    }
});
