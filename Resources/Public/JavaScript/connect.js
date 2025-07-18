import DocumentService from '@typo3/core/document-service.js';

DocumentService.ready().then(() => {
    let windowObjectReference = null;
    let previousUrl = null;

    const openSignInWindow = (url, name) => {
        window.removeEventListener('message', receiveMessage);
        const strWindowFeatures = 'toolbar=no, menubar=no, width=600, height=700, top=100, left=100';

        if (windowObjectReference === null || windowObjectReference.closed) {
            windowObjectReference = window.open(url, name, strWindowFeatures);
        } else if (previousUrl !== url) {
            windowObjectReference = window.open(url, name, strWindowFeatures);
            windowObjectReference.focus();
        } else {
            windowObjectReference.focus();
        }

        window.addEventListener('message', event => receiveMessage(event), false);
        previousUrl = url;
    };

    const receiveMessage = event => {
        if (
            !event.isTrusted
            || event.origin !== window.location.origin
            || event.source.origin !== window.location.origin
        ) {
            // security check
            return false;
        }
        document.querySelector('[name="_savedok"]').click()
    };

    const connectButtons = document.querySelectorAll('button[data-connect-uri]');
    connectButtons.forEach(btn => {
        btn.addEventListener('click', (evt) => {
            openSignInWindow(evt.target.getAttribute('data-connect-uri'), 'oauth2-authenticate')
        })
    });
});
