function hideField() {
    var fieldsContainers = document.querySelectorAll('.jtfileupload');

    fieldsContainers.forEach(function (el, i) {
        var uploadField = el.querySelector('input[type=file]');

        uploadField.disabled = true;

        var uploadFieldId = uploadField.id;

        var checkBox = document.getElementById(uploadFieldId + '_choverride');

        checkBox.addEventListener('click', function () {
            hideShowUpload(uploadField, checkBox);
        });
    });
};
jtfileuploadReady(hideField);

function hideShowUpload(uploadField, checkBox) {
    if (checkBox.checked == true) {
        uploadField.disabled = false;
    } else {
        uploadField.disabled = true;
    }

};