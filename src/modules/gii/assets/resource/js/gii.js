// $(function () {
//     // model generator: hide class name inputs and show psr class name checkbox
//     // when table name input contains *
//     $('#orangins-model-generator #generator-tablename').change(function () {
//         var show = ($(this).val().indexOf('*') === -1);
//         $('.field-generator-modelclass').toggle(show);
//         if ($('#generator-generatequery').is(':checked')) {
//             $('.field-generator-queryclass').toggle(show);
//         }
//         $('.field-generator-caseinsensitive').toggle(!show);
//     }).change();
//
//     // model generator: translate table name to model class
//     $('#orangins-model-generator #generator-tablename').on('blur', function () {
//         var tableName = $(this).val();
//         var tablePrefix = $(this).attr('table_prefix') || '';
//         if (tablePrefix.length) {
//             // if starts with prefix
//             if (tableName.slice(0, tablePrefix.length) === tablePrefix) {
//                 // remove prefix
//                 tableName = tableName.slice(tablePrefix.length);
//             }
//         }
//         if ($('#generator-modelclass').val() === '' && tableName && tableName.indexOf('*') === -1) {
//             var modelClass = '';
//             $.each(tableName.split(/\.|\_/), function () {
//                 if (this.length > 0)
//                     modelClass += this.substring(0, 1).toUpperCase() + this.substring(1);
//             });
//             $('#generator-modelclass').val("Phabricator" + modelClass).blur();
//         }
//         if ($('#generator-queryclass').val() === '' && tableName && tableName.indexOf('*') === -1) {
//             var modelClass = '';
//             $.each(tableName.split(/\.|\_/), function () {
//                 if (this.length > 0)
//                     modelClass += this.substring(0, 1).toUpperCase() + this.substring(1);
//             });
//             $('#generator-queryclass').val("Phabricator" + modelClass + "Query").blur();
//         }
//     });
// });