'use strict';

import './Polyfills';
import './PrototypeExtensions';

import YQuery from './YQuery';
import YBoard from './YBoard';
import Toast from './Toast';
import Modal from './Modal';

YQuery.ajaxSetup({
    // AJAX options
    'timeout': 10000,
    'errorFunction': function(xhr) {
        let errorMessage = xhr.responseText;
        let errorTitle = messages.errorOccurred;
        if (xhr.responseText.length === 0 && xhr.readyState === 0 && xhr.status === 0) {
            errorMessage = messages.networkError;
        } else {
            if (xhr.responseText === 'timeout') {
                errorMessage = messages.timeoutWarning;
            } else {
                try {
                    let text = JSON.parse(xhr.responseText);
                    errorMessage = text.message;
                    if (typeof text.title !== 'undefined' && text.title !== null && text.title.length !== 0) {
                        errorTitle = text.title;
                    }
                } catch (e) {
                    errorMessage = xhr.responseText;
                }
            }
        }

        if (xhr.status === 410) {
            Toast.warning(errorMessage);
        } else if (xhr.status === 418) {
            Toast.error(errorMessage);
        } else {
            Toast.error(errorMessage, errorTitle);
        }
    },
    'timeoutFunction': function(xhr) {
        Toast.error(messages.timeoutWarning);
    },
}, {
    // Headers
    'X-CSRF-Token': typeof csrfToken !== 'undefined' ? csrfToken : null,
});

window.YBoard = YBoard;
window.YQuery = YQuery;
window.Modal = Modal;
