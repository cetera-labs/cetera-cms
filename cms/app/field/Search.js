Ext.define('Cetera.field.Search', {

    extend:'Ext.form.field.Trigger',

    initComponent : function(){
        this.callParent();
        
        this.on('specialkey', function(f, e){
            if(e.getKey() == e.ENTER){
                this.onTrigger2Click();
            }
        }, this);
    },

    validationEvent:false,
    validateOnBlur:false,
    trigger1Cls:'x-form-clear-trigger',
    trigger2Cls:'x-form-search-trigger',

    width:180,
    hasSearch : false,
    paramName : 'query',
    reloadStore : true,

    onTrigger1Click : function(){
        this.setValue('');
        if(this.hasSearch){
            this.store.proxy.extraParams = this.store.proxy.extraParams || {};
            this.store.proxy.extraParams[this.paramName] = '';
            if (this.reloadStore) {
                var o = {start: 0};
                this.store.load({params:o});
            }
            this.hasSearch = false;
            this.fireEvent('search', {
            });             
        }
    },

    onTrigger2Click : function(){
        var v = this.getRawValue();
        if(v.length < 1){
            this.onTrigger1Click();
            return;
        }
        this.store.proxy.extraParams = this.store.proxy.extraParams || {};
        this.store.proxy.extraParams[this.paramName] = v;
        if (this.reloadStore) {
            var o = {start: 0};
            this.store.load({params:o});
        }
        this.hasSearch = true;
        this.fireEvent('search', {
        });        
    }
});