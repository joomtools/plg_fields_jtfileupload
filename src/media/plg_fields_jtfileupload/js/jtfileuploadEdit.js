function hideField() {
	var fieldsContainers = document.querySelectorAll('.jtfileupload');

	fieldsContainers.forEach(function (el, i) {
		var uploadField = el.querySelector('input[type=file]'),
			uploadFieldId = uploadField.id,
			checkBox = document.getElementById(uploadFieldId + '_choverride');

		uploadField.disabled = true;

		checkBox.addEventListener('click', function () {
			hideShowUpload(uploadField, checkBox);
		});
	});
}
jtfileuploadReady(hideField);

function hideShowUpload(uploadField, checkBox) {
	if (checkBox.checked == true) {
		uploadField.disabled = false;

		return;
	}

	uploadField.disabled = true;
}
