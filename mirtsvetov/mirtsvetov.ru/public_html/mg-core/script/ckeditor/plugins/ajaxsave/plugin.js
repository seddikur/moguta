(function()
{
  var saveCmd =
  {
    modes : { wysiwyg:1, source:1 },
    exec : function( editor )
    {	
	   if(confirm(lang.APPLY_INLINE_EDIT+'?')){
          var id = $(editor.element.$).data('item-id');
          var table = $(editor.element.$).data('table');           
          var field = $(editor.element.$).data('field');
          var dir = $(editor.element.$).data('dir');
          var cleanImages = false;
          if ($(editor.element.$).data('clean-images')) {
            cleanImages = true;
          }
          var content = editor.getData();
          admin.fastSaveField(table,field,id,content,dir,cleanImages);     
        }	
    }
  }
  var pluginName = 'ajaxsave';
  CKEDITOR.plugins.add( pluginName,
  {
     init : function( editor )
     {	 
	
		if($(editor.element.$).attr('contenteditable')=='true'){
			var command = editor.addCommand( pluginName, saveCmd );
			editor.ui.addButton( 'ajaxsave',
			 {
				label : 'Сохранить изменения',
				command : pluginName,
				icon: "plugins/ajaxsave/save-icon.png",
				toolbar: 'saveContent,1',
				contenteditable: false
			 });
	    }
      $(document).trigger('CKEDITOR_ajaxsave_inited');
     }
   });
})();