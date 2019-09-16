/**
 * @provides javelin-behavior-choose-control
 * @requires javelin-behavior
 *           javelin-stratcom
 *           javelin-dom
 *           javelin-workflow
 */

JX.behavior('ueditor-control', function (config) {
    var ue = UE.getEditor(config.id);

    UE.Editor.prototype.placeholder = function (justPlainText) {
        var _editor = this;
        _editor.addListener("focus", function () {
            var localHtml = _editor.getPlainTxt();
            if ($.trim(localHtml) === $.trim(justPlainText)) {
                _editor.setContent(" ");
            }
        });
        _editor.addListener("blur", function () {
            var localHtml = _editor.getContent();
            if (!localHtml) {
                _editor.setContent(justPlainText);
            }
        });
        _editor.ready(function () {
            _editor.fireEvent("blur");
        });
    };
    //placeholder内容(只能是文本内容)
    ue.placeholder("请输入文字");
});
