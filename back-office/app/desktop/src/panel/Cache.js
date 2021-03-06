Ext.define('Cetera.panel.Cache', {
	
	extend:'Ext.grid.Panel',
	
	alias : 'widget.cache',

	padding: 5,
	
	title: _('Кэширование'),
	
    columns: [
        {
			header: _('Кэш'), 
			dataIndex: 'name', 
			flex: 1		
		},
        {
			header: _('Размер'), 
			dataIndex: 'size', 
			flex: 1		
		}
    ],	
	
	store: {
		fields: [
			'name','size'
		],
		proxy: {
			 type: 'ajax',
			 url: '/cms/include/data_cache.php',
			 reader: {
				 type: 'json',
				 rootProperty: 'rows'
			 }
		},
		autoLoad: true		
	},
	
	tbar: [
		{ 
			iconCls:'x-fa fa-sync',
			tooltip: _('Обновить'),
			handler: function () { 
				this.up('grid').getStore().load();
			}
		},
		'-',
		{
			iconCls:'x-fa fa-broom',
			text:_('Очистить устаревшее'),
			handler: function () { 
				this.up('grid').getStore().load({
					params: {
						action: 'clear'
					}
				});			
			}
		},	
		'-',
		{ 
			text: _('Очистить кэш Twig'),
			handler: function () { 
				this.up('grid').getStore().load({
					params: {
						action: 'twig'
					}
				});
			}
		},		
		'-',
		{ 
			iconCls:'x-fa fa-trash',
			text: _('Удалить все'),
			handler: function () { 
				this.up('grid').getStore().load({
					params: {
						action: 'delete'
					}
				});
			}
		}		
	]
	
});