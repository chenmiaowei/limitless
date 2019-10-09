/**
 * @provides javelin-behavior-phui-file-upload
 * @requires javelin-behavior
 *           javelin-stratcom
 *           javelin-dom
 *           phuix-dropdown-menu
 */

JX.behavior('phui-file-upload-ajax', function (config) {
    var input;
    var container = JX.$(config.id);
    try {
        input = JX.DOM.find(container, 'input', 'file-upload-ajax-input');
    } catch (ex) {
        console.log(ex);
        return;
    }

    function startUpload() {
        var files = input.files;


        if (!files || !files.length) {
            return;
        }

        var state = {
            input: input,
            waiting: 0,
            phids: []
        };

        var callback = JX.bind(null, didUpload, state);
        var process_callback = JX.bind(null, didProgress, state);

        var dummy = input;
        var uploader = new JX.PhabricatorDragAndDropFileUpload(dummy)
            .setURI(config.uploadURI)
            .setChunkThreshold(config.chunkThreshold);

        uploader.listen('didUpload', callback);
        uploader.listen('willUpload', process_callback);
        uploader.start();

        for (var ii = 0; ii < files.length; ii++) {
            state.waiting++;
            uploader.sendRequest(files[ii]);
        }
    }

    function didProgress(state, file) {
        var content = JX.DOM.find(container, 'div', 'file-upload-ajax-content');
        JX.DOM.setContent(content, JX.$N('i', {className: 'icon-spinner2 spinner icon-2x text-success p-3 mt-3'}));
        JX.DOM.appendContent(content, JX.$N('h5', {className: 'card-title mb-3'}, config.uploadButtonText));
    }

    function didUpload(state, file) {
        var hiddenInput = JX.DOM.find(container, 'input', 'file-upload-ajax-input-hidden');
        hiddenInput.value = file.getPHID();

        var content = JX.DOM.find(container, 'div', 'file-upload-ajax-content');

        if(file.getRawFileObject() && file.getRawFileObject().type) {
            if(file.getRawFileObject().type.indexOf('image') > -1 ){
                JX.DOM.setContent(content, JX.$N("img", {src: file.getURI(), style: {width: '100%'}}));
            } else if(file.getRawFileObject().type.indexOf('sheet') > -1) {
                JX.DOM.setContent(content, JX.$N("i", {className: "icon-file-spreadsheet2 icon-2x text-success p-3 mt-3"}));
                JX.DOM.appendContent(content, JX.$N("h5", {className: "card-title mb-3"},  file.getName()));
            } else if(file.getRawFileObject().type.indexOf('csv') > -1) {
                JX.DOM.setContent(content, JX.$N("i", {className: "icon-file-xml icon-2x text-success p-3 mt-3"}));
                JX.DOM.appendContent(content, JX.$N("h5", {className: "card-title mb-3"},  file.getName()));
            } else if(file.getRawFileObject().type.indexOf('document') > -1) {
                JX.DOM.setContent(content, JX.$N("i", {className: "icon-file-presentation icon-2x text-success p-3 mt-3"}));
                JX.DOM.appendContent(content, JX.$N("h5", {className: "card-title mb-3"},  file.getName()));
            } else {
                JX.DOM.setContent(content, JX.$N("i", {className: "icon-file-text icon-2x text-success p-3 mt-3"}));
                JX.DOM.appendContent(content, JX.$N("h5", {className: "card-title mb-3"},  file.getName()));
            }
        } else {
            JX.DOM.setContent(content, JX.$N("img", {src: file.getURI(), style: {width: '100%'}}));
        }
    }

    JX.DOM.listen(input, 'change', null, startUpload);


    //
    // function didUpload(state, file) {
    //   state.phids.push(file.getPHID());
    //   state.waiting--;
    //
    //   if (state.waiting) {
    //     return;
    //   }
    //
    //   state.workflow
    //     .addData(config.inputName, state.phids.join(', '))
    //     .resume();
    // }
    //
    // JX.Workflow.listen('start', function(workflow) {
    //   var form = workflow.getSourceForm();
    //   if (!form) {
    //     return;
    //   }
    //
    //   var input;
    //   try {
    //     input = JX.$(config.fileInputID);
    //   } catch (ex) {
    //     return;
    //   }
    //
    //   var local_form = JX.DOM.findAbove(input, 'form');
    //   if (!local_form) {
    //     return;
    //   }
    //
    //   if (local_form !== form) {
    //     return;
    //   }
    //
    //   startUpload(workflow, input);
    // });

});
