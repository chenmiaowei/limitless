/**
 * @provides javelin-behavior-phui-file-upload
 * @requires javelin-behavior
 *           javelin-stratcom
 *           javelin-dom
 *           phuix-dropdown-menu
 */

JX.behavior('phui-file-upload-ajax', function (config) {
    (new JX.PhabricatorFileUploadControl(config))
        .show();
    // var container = JX.$(config.id);
    // var inputItem = JX.DOM.find(container, 'div', 'file-upload-ajax-item');
    // var input = JX.DOM.find(inputItem, 'input', 'file-upload-ajax-input');
    //
    // function startUpload() {
    //     var files = input.files;
    //     console.log(files);
    //
    //     if (!files || !files.length) {
    //         return;
    //     }
    //
    //     var state = {
    //         input: input,
    //         waiting: 0,
    //         phids: []
    //     };
    //
    //     var callback = JX.bind(null, didUpload, state);
    //     var process_callback = JX.bind(null, didProgress, state);
    //
    //     var dummy = input;
    //     var uploader = new JX.PhabricatorDragAndDropFileUpload(dummy)
    //         .setURI(config.uploadURI)
    //         .setChunkThreshold(config.chunkThreshold);
    //
    //     uploader.listen('didUpload', callback);
    //     uploader.listen('willUpload', process_callback);
    //     uploader.start();
    //
    //     for (var ii = 0; ii < files.length; ii++) {
    //         state.waiting++;
    //         uploader.sendRequest(files[ii]);
    //     }
    // }
    //
    // function didProgress(state, file) {
    //     var oldContent = JX.DOM.find(inputItem, 'div', 'file-upload-ajax-item-content');
    //     JX.DOM.setContent(oldContent, [
    //         JX.$N("i", {className: "icon-spinner2 spinner icon-2x text-success p-3 mt-3"}),
    //         JX.$N("h5", {className: "card-title mb-3"}, config.uploadButtonText)
    //     ]);
    // }
    //
    // function didDelete(node) {
    //     JX.DOM.remove(node);
    // }
    //
    // function didUpload(state, file) {
    //     let cloneNode = inputItem.cloneNode(true);
    //     let closeButtonHtml = JX.$H('<div class="position-absolute font-size-lg" data-sigil="file-upload-ajax-item-close" style="right: 10px; top: 10px;"><i class="visual-only fa fa-close" data-meta="0_3" aria-hidden="true"></i></div>');
    //     JX.DOM.prependContent(cloneNode, closeButtonHtml);
    //     let findInput = JX.DOM.find(cloneNode, 'input', 'file-upload-ajax-input');
    //     JX.DOM.remove(findInput);
    //     var hiddenInput = JX.DOM.find(cloneNode, 'input', 'file-upload-ajax-input-hidden');
    //     hiddenInput.value = file.getPHID();
    //     var content = JX.DOM.find(cloneNode, 'div', 'file-upload-ajax-item-content');
    //     JX.DOM.prependContent(container, cloneNode);
    //
    //
    //     let closeButton = JX.DOM.find(cloneNode, 'div', 'file-upload-ajax-item-close');
    //     JX.DOM.listen(closeButton, 'click', null, JX.bind(null, didDelete, cloneNode));
    //
    //     if (file.getRawFileObject() && file.getRawFileObject().type) {
    //         if (file.getRawFileObject().type.indexOf('image') > -1) {
    //             JX.DOM.setContent(content, [
    //                 JX.$N("img", {src: file.getURI(), style: {width: '100%'}})
    //             ]);
    //         } else if (file.getRawFileObject().type.indexOf('sheet') > -1) {
    //             JX.DOM.setContent(content, [
    //                 JX.$N("i", {className: "icon-file-spreadsheet2 icon-2x text-success p-3 mt-3"}),
    //                 JX.$N("h5", {className: "card-title mb-3"}, file.getName())
    //             ]);
    //         } else if (file.getRawFileObject().type.indexOf('csv') > -1) {
    //             JX.DOM.setContent(content, [
    //                 JX.$N("i", {className: "icon-file-xml icon-2x text-success p-3 mt-3"}),
    //                 JX.$N("h5", {className: "card-title mb-3"}, file.getName())
    //             ]);
    //         } else if (file.getRawFileObject().type.indexOf('document') > -1) {
    //             JX.DOM.setContent(content, [
    //                 JX.$N("i", {className: "icon-file-presentation icon-2x text-success p-3 mt-3"}),
    //                 JX.$N("h5", {className: "card-title mb-3"}, file.getName())
    //             ]);
    //         } else {
    //             JX.DOM.setContent(content, [
    //                 JX.$N("i", {className: "icon-file-text icon-2x text-success p-3 mt-3"}),
    //                 JX.$N("h5", {className: "card-title mb-3"}, file.getName())
    //             ]);
    //         }
    //     } else {
    //         JX.DOM.setContent(content, JX.$N("img", {src: file.getURI(), style: {width: '100%'}}));
    //     }
    //
    //     var oldContent = JX.DOM.find(inputItem, 'div', 'file-upload-ajax-item-content');
    //     JX.DOM.setContent(oldContent, [
    //         JX.$N("i", {className: "icon-file-plus icon-2x text-success p-3 mt-3"}),
    //         JX.$N("h5", {className: "card-title mb-3"}, config.uploadButtonText)
    //     ]);
    // }
    //
    // JX.DOM.listen(input, 'change', null, startUpload);
});
