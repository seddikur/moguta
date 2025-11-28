(function()
{
  var saveCmd =
  {
    modes : { wysiwyg:1, source:1 },
    exec : function( editor )
    {	
        const content = $(editor.element.$).html();          
        const nodeId = $(editor.element.$).closest('[data-settings]').data('settings');
        const settingsName = $(editor.element.$).data('setting');

        noteEditor.saveCkeditorNote(nodeId, settingsName, content);
      }
  }

  
  var pluginName = 'savedata';
  CKEDITOR.plugins.add( pluginName,
  {
     init : function( editor )
     {	 
	
		if($(editor.element.$).attr('contenteditable')=='true'){
			var command = editor.addCommand( pluginName, saveCmd );
			editor.ui.addButton( 'savedata',
			 {
				label : 'Сохранить изменения',
				command : pluginName,
				icon: "plugins/ajaxsave/save-icon.png",
				toolbar: 'saveContent,1',
				contenteditable: false
			 });
	    }
     }
   });
})();