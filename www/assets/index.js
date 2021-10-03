// Assets
import './css/style.scss';

// Application
import netteForms from 'nette-forms';
import * as changeFormSubmit from './js/change-form-submit';
import * as form from './js/form';
import * as formWarnings from './js/form-warnings';
import * as icons from './js/icons';
import * as list from './js/list';
import * as noscript from './js/noscript';

netteForms.initOnLoad();
changeFormSubmit.register();
form.register();
formWarnings.register();
icons.register();
list.register();
noscript.register();
