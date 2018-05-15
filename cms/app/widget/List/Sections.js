// �������� ������� 
Ext.require('Cetera.field.Folder');
Ext.require('Cetera.field.FileEditable');
Ext.require('Cetera.field.WidgetTemplate');

Ext.define('Cetera.widget.List.Sections', {

    extend: 'Cetera.widget.Widget',
    
    initComponent : function() {
        this.formfields = [{
            fieldLabel: Config.Lang.catalog,
            name: 'catalog',
            xtype: 'folderfield'
        },{
            xtype: 'numberfield',
            name: 'limit',
            fieldLabel: _('���-�� �����������'),
            maxValue: 999,
            minValue: 1,
            allowBlank: false
        },
		
		{
			xtype: 'fieldcontainer',
			cls: 'x-field',
			layout: 'hbox',
			fieldLabel: Config.Lang.sort,
			defaults: {
				flex:1,
				xtype: 'textfield',
				hideLabel: true
			},
			items: [{
				name: 'order',
				allowBlank: false,
				margin: '0 5 0 0'
			},
			new Ext.form.ComboBox({
				name:'sort',
				store: new Ext.data.SimpleStore({
					fields: ['name', 'value'],
					data : [
						[_('�����������'), 'ASC'],
						[_('��������'), 'DESC']              
					]
				}),
				valueField:'value',
				displayField:'name',
				queryMode: 'local',
				triggerAction: 'all',
				editable: false,
			})]
			
		},		
		
		{
            name: 'catalog_link',
            fieldLabel: _('������ �� ������'),
        },
		
		{
			xtype: 'fieldcontainer',
			cls: 'x-field',
			fieldLabel: _('��������'),
			layout: 'hbox',
			defaults: {
				inputValue:     1,
				uncheckedValue: 0,
				margin: '0 5 0 0'				
			},			
			items: [{
				xtype:          'checkbox',
				boxLabel:       _('�������� ���������'),
				name:           'paginator'
			}, {
				xtype:          'checkbox',
				boxLabel:       _('AJAX ���������'),
				name:           'ajax'
			}, {
				flex:1,
				xtype:          'checkbox',
				boxLabel:       _('����������� �����'),
				name:           'infinite'
			}]
		},
		{
			xtype: 'fieldcontainer',
			cls: 'x-field',
			layout: 'hbox',
			defaults: {
				flex:1
			},			
			items: [{
				xtype: 'textfield',
				name: 'page_param',
				labelWidth: 150,
				fieldLabel: _('query ��������'),
				margin: '0 5 0 0'
			}, {
				xtype: 'textfield',
				name: 'paginator_url',
				fieldLabel: _('������ �� ��������')
			}]
		},			
		{
			xtype: 'widgettemplate',
			widget: 'List'
        }];
        this.callParent();
    }

});