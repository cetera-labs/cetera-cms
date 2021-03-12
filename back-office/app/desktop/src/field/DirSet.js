Ext.define('Cetera.field.DirSet', {

    extend: 'Cetera.field.Set',

    onAddItem: function() {
    
        if (!this.siteTree) {
            this.siteTree = Ext.create('Cetera.window.SiteTree', {
                from: this.from,
                materials : 0,
                nocatselect: 0 
            });
            this.siteTree.on('select', function(res) {
                this.addItem({id: res.id, name: res.name_to});
            },this);
        }
        this.siteTree.show(); 

    },
      
	getButtons: function() {
		return [{
            xtype   : 'button',
            iconCls : 'icon-new',
            tooltip : Config.Lang.add,
            handler : this.onAddItem,
            scope   : this
        },{
            xtype   : 'button',
            iconCls : 'icon-delete',
            tooltip : Config.Lang.remove,
            handler : this.removeItem,
            scope   : this
        }];
	}	
});