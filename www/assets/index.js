// Application
import netteForms from 'nette-forms';
import * as changeFormSubmit from './js/change-form-submit';
import * as checkboxes from './js/checkboxes';
import * as form from './js/form';
import * as formWarnings from './js/form-warnings';
import * as icons from './js/icons';
import * as list from './js/list';
import * as nextrasForms from './js/nextras-forms';
import * as noscript from './js/noscript';

netteForms.initOnLoad();
nextrasForms.register(netteForms);
changeFormSubmit.register();
checkboxes.register();
form.register(netteForms);
formWarnings.register();
icons.register();
list.register();
noscript.register();
