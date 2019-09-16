$(function () {
    $.fn.select2.amd.require(['select2/selection/search'], function (Search) {
        Search.prototype.searchRemoveChoice = function (decorated, item) {
            this.trigger('unselect', {
                data: item
            });

            this.$search.val('');
            this.handleSearch();
        };
    }, null, true);
});