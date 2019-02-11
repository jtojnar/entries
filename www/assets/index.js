// Assets
import './css/style.scss';

// Application
import netteForms from 'nette-forms';
import * as form from './js/form';
import * as formWarnings from './js/form-warnings';
import * as icons from './js/icons';
import * as list from './js/list';

netteForms.initOnLoad();
form.register();
formWarnings.register();
icons.register();
list.register();
