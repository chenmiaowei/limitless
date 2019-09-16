/**
 * @provides javelin-behavior-bulk-job-reload
 * @requires javelin-behavior
 *           javelin-uri
 */

JX.behavior('bulk-job-reload', function () {

    // TODO: It would be nice to have a pretty Ajax progress bar here, but just
    // reload the page for now.

    function reload() {
        // console.log(JX.$U());
        // JX.$U().go();
        location.reload();
    }

    setTimeout(reload, 1000);

});
