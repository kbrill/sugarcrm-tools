({
    extendsFrom: 'RecordlistView',
    initialize: function (options) {
        app.view.invokeParent(this, {type: 'view', name: 'recordlist', method: 'initialize', args:[options]});
        //add listener for custom button
        this.context.on('list:massrestore:fire', this.massrestore, this);
    },
    massrestore : function() {
        var idCSV = this.context.get('mass_collection').models;

        $.ajax({
            url: 'index.php?module=CSTM_ANIMALS&action=ADD_TO_CIRCUS',
            type: 'POST',
            data: {uid: idCSV},
            success: function(errorResponse) {
                if(errorResponse != '') {
                    app.alert.show('bad-add-to-circus', {
                        level: 'error',
                        messages: errorResponse,
                        autoClose: false
                    });
                }
            }
        });
    }
})