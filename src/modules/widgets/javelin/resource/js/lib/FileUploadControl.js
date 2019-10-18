/**
 * @requires javelin-install
 *           javelin-dom
 *           phabricator-notification
 * @provides phabricator-file-upload
 * @javelin
 */

JX.install('PhabricatorFileUploadControl', {
    construct: function (config) {
        this.setId(config.id);
        this.setIsMultiple(config.isMultiple);
        this.setUploadButtonText(config.uploadButtonText);
        this.setInputName(config.inputName);
        this.setUploadURI(config.uploadURI);
        this.setChunkThreshold(config.chunkThreshold);
    },

    properties: {
        'id': null,
        'isMultiple': null,
        'uploadButtonText': null,
        'inputName': null,
        'uploadURI': null,
        'chunkThreshold': null
    },

    members: {
        _container: null,
        _inputItem: null,
        _input: null,

        show: function () {
            this._container = JX.$(this.getId());
            this._inputItem = JX.DOM.find(this._container, 'div', 'file-upload-ajax-item');
            this._input = JX.DOM.find(this._container, 'input', 'file-upload-ajax-input');
            JX.DOM.listen(this._input, 'change', null, JX.bind(this, this.startUpload));
        },

        startUpload: function () {
            let files = this._input.files;
            if (!files || !files.length) {
                return;
            }

            let state = {
                input: this._input,
                waiting: 0,
                phids: []
            };

            let callback = JX.bind(this, this.didUpload, state);
            let process_callback = JX.bind(this, this.didProgress, state);

            let dummy = this._input;
            let uploader = new JX.PhabricatorDragAndDropFileUpload(dummy)
                .setURI(this.getUploadURI())
                .setChunkThreshold(this.getChunkThreshold());

            uploader.listen('didUpload', callback);
            uploader.listen('willUpload', process_callback);
            uploader.start();

            for (let ii = 0; ii < files.length; ii++) {
                state.waiting++;
                uploader.sendRequest(files[ii]);
            }
        },

        didProgress: function (state, file) {
            let oldContent = JX.DOM.find(this._inputItem, 'div', 'file-upload-ajax-item-content');
            JX.DOM.setContent(oldContent, [
                JX.$N("i", {className: "icon-spinner2 spinner icon-2x text-success p-3 mt-3"}),
                JX.$N("h5", {className: "card-title mb-3"}, this.getUploadButtonText())
            ]);
        },

        didDelete: function (node) {
            JX.DOM.remove(node);
        },

        didUpload: function (state, file) {
            let content;

            if(this.getIsMultiple()) {
                let cloneNode = this._inputItem.cloneNode(true);
                let closeButtonHtml = JX.$H('<div class="position-absolute font-size-lg" data-sigil="file-upload-ajax-item-close" style="right: 10px; top: 10px;"><i class="visual-only fa fa-close" data-meta="0_3" aria-hidden="true"></i></div>');
                JX.DOM.prependContent(cloneNode, closeButtonHtml);
                let findInput = JX.DOM.find(cloneNode, 'input', 'file-upload-ajax-input');
                JX.DOM.remove(findInput);
                let hiddenInput = JX.DOM.find(cloneNode, 'input', 'file-upload-ajax-input-hidden');
                hiddenInput.value = file.getPHID();
                content = JX.DOM.find(cloneNode, 'div', 'file-upload-ajax-item-content');
                JX.DOM.prependContent(this._container, cloneNode);
                let closeButton = JX.DOM.find(cloneNode, 'div', 'file-upload-ajax-item-close');
                JX.DOM.listen(closeButton, 'click', null, JX.bind(this, this.didDelete, cloneNode));
            } else {
                content = JX.DOM.find(this._inputItem, 'div', 'file-upload-ajax-item-content');
            }

            if (file.getRawFileObject() && file.getRawFileObject().type) {
                if (file.getRawFileObject().type.indexOf('image') > -1) {
                    JX.DOM.setContent(content, [
                        JX.$N("img", {src: file.getURI(), style: {width: '100%'}})
                    ]);
                } else if (file.getRawFileObject().type.indexOf('sheet') > -1) {
                    JX.DOM.setContent(content, [
                        JX.$N("i", {className: "icon-file-spreadsheet2 icon-2x text-success p-3 mt-3"}),
                        JX.$N("h5", {className: "card-title mb-3"}, file.getName())
                    ]);
                } else if (file.getRawFileObject().type.indexOf('csv') > -1) {
                    JX.DOM.setContent(content, [
                        JX.$N("i", {className: "icon-file-xml icon-2x text-success p-3 mt-3"}),
                        JX.$N("h5", {className: "card-title mb-3"}, file.getName())
                    ]);
                } else if (file.getRawFileObject().type.indexOf('document') > -1) {
                    JX.DOM.setContent(content, [
                        JX.$N("i", {className: "icon-file-presentation icon-2x text-success p-3 mt-3"}),
                        JX.$N("h5", {className: "card-title mb-3"}, file.getName())
                    ]);
                } else {
                    JX.DOM.setContent(content, [
                        JX.$N("i", {className: "icon-file-text icon-2x text-success p-3 mt-3"}),
                        JX.$N("h5", {className: "card-title mb-3"}, file.getName())
                    ]);
                }
            } else {
                JX.DOM.setContent(content, JX.$N("img", {src: file.getURI(), style: {width: '100%'}}));
            }

            if(this.getIsMultiple()) {
                let oldContent = JX.DOM.find(this._inputItem, 'div', 'file-upload-ajax-item-content');
                JX.DOM.setContent(oldContent, [
                    JX.$N("i", {className: "icon-file-plus icon-2x text-success p-3 mt-3"}),
                    JX.$N("h5", {className: "card-title mb-3"}, this.getUploadButtonText())
                ]);
            }
        },

    }
});
