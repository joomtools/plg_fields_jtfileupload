function jtfileuploadReady(fn) {
    if (document.attachEvent ? document.readyState === "complete" : document.readyState !== "loading") {
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
};

function jtFileUploadEnctype() {
    document.querySelectorAll('form[name="adminForm"]')[0].setAttribute('enctype', 'multipart/form-data');
};

jtfileuploadReady(jtFileUploadEnctype);