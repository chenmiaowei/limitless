/**
 * Override the default yii confirm dialog. This function is
 * called by yii when a confirmation is requested.
 *
 * @param message
 * @param ok
 * @param cancel
 */
yii.confirm = function (message, ok, cancel) {
    swal({
        title: 'Are you sure?',
        text: message,
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'No, cancel!',
        confirmButtonClass: 'btn btn-success',
        cancelButtonClass: 'btn btn-danger',
        buttonsStyling: false
    }).then(function (event) {
        if (!event.value) {
            !cancel || cancel();
        } else {
            !ok || ok();
        }
    });
};